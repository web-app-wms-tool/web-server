<?php

namespace App\Jobs;

use App\Models\ConvertedFile;
use App\Models\Task;
use App\Models\UploadedFile;
use App\Traits\GeoserverTrait;
use App\Traits\Uuid;
use DB;
use Process;

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
            $response = $this->postGeoserverData($url_featuretypes, ['featureType' => ['name' => $uuid]]);
            $cb_show("Publish layer {$db_name}:{$uuid} done");

            ConvertedFile::create([
                'layer_name' => "$db_name:$uuid",
                'geoserver_ref' => '',
                'uuid' => $uuid,
                'task_id' => $this->task->id,
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
            $converted_file = ConvertedFile::where('uuid', $uuid)->first();
            if (!empty($converted_file)) {
                $converted_file->delete();
            }
        });
    }
}
