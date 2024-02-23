<?php

namespace App\Jobs;

use App\Constants\OutputType;
use App\Helpers\ZipHelper;
use App\Models\ConvertedFile;
use App\Models\Task;
use App\Models\UploadedFile;
use App\Traits\Uuid;
use File;
use Process;
use Storage;
use ZipArchive;

class HandleConvertFileJob extends AJob
{
    protected $type = 'CONVERT_FILE';
    public $timeout = 0;
    /**
     * Create a new job instance.
     */
    protected $data;
    protected $task;
    protected $options;
    protected $uuid;
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
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->cb_handle(function ($cb_show) {
            $cb_show("Convert {$this->data->name} to {$this->options['output_type']} start");
            $uuid = $this->uuid;
            $disk = Storage::disk('public');
            $relative_folder_path = "files/convert/$uuid";
            if (!$disk->exists($relative_folder_path)) {
                $disk->makeDirectory("$relative_folder_path"); //creates directory
                $cb_show("Create folder {$disk->path($relative_folder_path)}");
            }
            $name = pathinfo($this->data->path, PATHINFO_FILENAME);

            $cmd = config('tool.ogr2ogr_path');
            $cmd .= " -sql \"select * from entities";
            $where = "where (OGR_GEOMETRY ilike " . join(" or OGR_GEOMETRY ilike ", $this->options['geometry_types']) . ")";
            $where .= " and (layer ilike " . join(" or layer ilike ", $this->options['layers']) . ")";
            $where .= "\"";
            $cmd .= " " . $where;
            $cmd .= " -s_srs {$this->data->srs} -t_srs {$this->options['srs']}";
            if ($this->options['output_type'] == OutputType::SHAPEFILE) {
                $cmd .= " -f \"ESRI Shapefile\" -skipfailures";
            } else {
                $cmd .= " -f {$this->options['output_type']}";
            }
            $file_path = "{$disk->path($relative_folder_path)}/{$name}.{$this->options['output_type']}";
            $cmd .= " $file_path";
            $cmd .= " {$this->data->dxf_path}";

            $process = Process::run($cmd);
            $output = $process->output();
            if ($process->failed() && $this->options['output_type'] != OutputType::SHAPEFILE) {
                if (empty($output)) {
                    $type = ucfirst(strtolower($this->options['output_type']));
                    $output = "Can not convert {$this->data->name} to $type";
                }
                throw new \Exception($output);
            }

            if ($this->options['output_type'] == OutputType::SHAPEFILE) {
                if (empty($disk->files("files/convert/{$uuid}"))) {
                    throw new \Exception("Can not convert {$this->data->name} to Shapefile");
                }
                $cb_show("Zipping all shapefile start");
                $file_path = $this->zipShapefile($disk->path("files/convert/{$uuid}"), $name);
                $cb_show("Zipping all shapefile done");
            }

            ConvertedFile::create([
                'name' => basename($file_path),
                'path' => $file_path,
                'task_id' => $this->task->id,
            ]);
            $cb_show("Convert {$this->data->name} to {$this->options['output_type']} done");
        });
    }

    public function failed(\Exception $exception): void
    {
        $this->cb_failed($exception, function ($cb_show) {
            $disk = Storage::disk('public');
            $relative_folder_path = "files/convert/{$this->uuid}";
            if ($disk->exists($relative_folder_path)) {
                $disk->deleteDirectory("$relative_folder_path");
                $cb_show("Delete folder $relative_folder_path");
            }
        });
    }

    public function zipShapefile(string $folder_path, string $name): string
    {
        $old_files = File::files($folder_path);

        $zip = new ZipArchive();
        $file_path = $folder_path . '/' . "$name.zip";
        if ($zip->open($file_path, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Cannot open ' . $file_path);
        }
        ZipHelper::addContent($zip, $folder_path);
        $zip->close();

        foreach ($old_files as $file) {
            File::delete($file->getRealPath());
        }

        return $file_path;
    }
}
