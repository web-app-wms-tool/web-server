<?php

namespace App\Jobs;

use App\Constants\Srs;
use App\Models\ConvertedLayer;
use App\Models\Task;
use App\Models\UploadedFile;
use App\Traits\GeoserverTrait;
use App\Traits\Uuid;
use DB;
use Process;
use proj4php\Point;
use proj4php\Proj4php;
use proj4php\Proj;

class HandleConvertFileJob extends AJob
{
    use GeoserverTrait;
    protected $type = 'CONVERT_FILE';
    public $timeout = 0;
    /**
     * Create a new job instance.
     */
    protected $data;
    protected $task;
    protected $options;
    protected $uuid;
    protected $db_config;
    public function __construct(Task $task, UploadedFile $data, $options)
    {
        parent::__construct($task);
        $this->uuid = Uuid::generateUuid();
        $this->data = $data;
        $this->options = $options;
        $this->options['geometry_types'] = array_map(function ($item) {
            return "'$item'";
        }, $this->options['geometry_types']);
        $this->options['layers'] = array_map(function ($item) {
            return "'$item'";
        }, $this->options['layers']);
        $this->db_config = DB::connection("pgsql-import")->getConfig();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->cb_handle(function ($cb_show) {
            $cb_show("Import {$this->data->name} into GeoServer as Postgis Database start");
            $uuid = $this->uuid;

            $workspace = [
                'default' => false,
                'name' => config('app.geoserver.workspace'),
            ];
            $response = $this->getGeoserverData("workspaces" . "/{$workspace['name']}");
            if ($response->failed()) {
                $this->postGeoserverData("workspaces", ['workspace' => $workspace]);
                $cb_show("Create workspace {$workspace['name']} done");
            }

            $db_name = $this->db_config["database"];
            $username = $this->db_config["username"];
            $host = $this->db_config["host"];
            $port = $this->db_config["port"];
            $password = $this->db_config["password"];
            $schema = $this->db_config["schema"];

            $workspace_name = !empty($workspace['name']) ? $workspace['name'] : config('app.geoserver.workspace');
            $url_datastore = "workspaces/$workspace_name/datastores";
            $repsonse = $this->getGeoserverData($url_datastore . "/$schema");
            if ($repsonse->failed()) {
                $payload = [
                    "dataStore" => [
                        "name" => $schema,
                        "connectionParameters" => [
                            "entry" => [
                                ["@key" => "host", "$" => $host],
                                ["@key" => "port", "$" => $port],
                                ["@key" => "database", "$" => $db_name],
                                ["@key" => "schema", "$" => $schema],
                                ["@key" => "user", "$" => $username],
                                ["@key" => "passwd", "$" => $password],
                                ["@key" => "dbtype", "$" => "postgis"],
                            ],
                        ],
                    ],
                ];
                $this->postGeoserverData($url_datastore, $payload);
                $cb_show("Create DataStore {$schema} done");
            }

            $cb_show("Import {$this->data->name} into PostgreSQL start");

            $cmd = config('tool.ogr2ogr_path');
            $cmd .= " -sql \"select * from entities";
            $where = "where (OGR_GEOMETRY ilike " . join(" or OGR_GEOMETRY ilike ", $this->options['geometry_types']) . ")";
            $where .= " and (layer ilike " . join(" or layer ilike ", $this->options['layers']) . ")";
            $where .= "\"";
            $cmd .= " " . $where;
            $cmd .= " -s_srs {$this->data->srs} -t_srs {$this->options['srs']}";

            if ($this->options['srs'] != Srs::DEFAULT) {
                $proj4 = new Proj4php();
                $s_proj4 = new Proj(Srs::DEFAULT, $proj4);
                $t_proj4 = new Proj($this->options['srs'], $proj4);
                $s_min_point = new Point($this->options['min_x'], $this->options['min_y'], $s_proj4);
                $t_min_point = $proj4->transform($t_proj4, $s_min_point);
                $s_max_point = new Point($this->options['max_x'], $this->options['max_y'], $s_proj4);
                $t_max_point = $proj4->transform($t_proj4, $s_max_point);
            }

            $min_x = $t_min_point->x ?? $this->options['min_x'];
            $min_y = $t_min_point->y ?? $this->options['min_y'];
            $max_x = $t_max_point->x ?? $this->options['max_x'];
            $max_y = $t_max_point->y ?? $this->options['max_y'];
            $cmd .= " -clipdst";
            $cmd .= " " . $min_x . " " . $min_y;
            $cmd .= " " . $max_x . " " . $max_y;
            $cmd .= " -f \"PostgreSQL\" PG:\"host=$host port=$port user=$username password=$password dbname=$db_name\"";
            $cmd .= " -lco SCHEMA=\"import\" -nln $uuid -append";
            $cmd .= " {$this->data->dxf_path}";

            $process = Process::run($cmd);
            $output = $process->output();
            if ($process->failed()) {
                if (empty($output)) {
                    $output = "Can not import {$this->data->name} into PostgreSQL done";
                }
                throw new \Exception($output);
            }
            $cb_show("Import {$this->data->name} into PostgreSQL done");

            $cb_show("Publish layer {$db_name}:{$uuid} start");
            $url_featuretypes = "workspaces/$workspace_name/datastores/$schema/featuretypes";
            $response = $this->postGeoserverData($url_featuretypes, [
                'featureType' => [
                    'name' => $uuid,
                    "srs" => $this->options['srs'],
                    "nativeBoundingBox" => [
                        "minx" => $t_min_point->x ?? $this->options['min_x'],
                        "miny" => $t_min_point->y ?? $this->options['min_y'],
                        "maxx" => $t_max_point->x ?? $this->options['max_x'],
                        "maxy" => $t_max_point->y ?? $this->options['max_y'],
                        "crs" => $this->options['srs'],
                    ],
                ],
            ]
            );
            $cb_show("Publish layer {$db_name}:{$uuid} done");

            ConvertedLayer::create([
                'layer_name' => "$db_name:$uuid",
                'srs' => $this->options['srs'],
                'geoserver_ref' => '',
                'uuid' => $uuid,
                'task_id' => $this->task->id,
                'metadata' => [
                    'bbox' => [
                        $t_min_point->x ?? $this->options['min_x'],
                        $t_min_point->y ?? $this->options['min_y'],
                        $t_max_point->x ?? $this->options['max_x'],
                        $t_max_point->y ?? $this->options['max_y'],
                        "srs" => $this->options['srs'],
                    ],
                ],
            ]);
            $cb_show("Import {$this->data->name} into GeoServer as Postgis Database done");
        });
    }

    public function failed(\Exception $exception): void
    {
        $schema = $this->db_config['schema'];
        $uuid = $this->uuid;
        $this->cb_failed($exception, function ($cb_show) use ($schema, $uuid) {
            DB::statement("DROP TABLE IF EXISTS \"$schema\".\"$uuid\" CASCADE;");
            $converted_file = ConvertedLayer::where('uuid', $uuid)->first();
            if (!empty($converted_file)) {
                $converted_file->delete();
            }
        });
    }
}
