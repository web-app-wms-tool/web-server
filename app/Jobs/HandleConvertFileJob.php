<?php

namespace App\Jobs;

use App\Constants\OutputType;
use App\Constants\TaskStatus;
use App\Helpers\ZipHelper;
use App\Models\ConvertedFile;
use App\Models\Task;
use App\Models\UploadedFile;
use App\Traits\Uuid;
use Carbon\Carbon;
use File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Process;
use Storage;
use ZipArchive;

class HandleConvertFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 0;
    /**
     * Create a new job instance.
     */
    protected $data;
    protected $task;
    protected $options;
    protected $uuid;
    public function __construct(UploadedFile $data, Task $task, $options)
    {
        $this->uuid = Uuid::generateUuid();
        $this->data = $data;
        $this->task = $task;
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
        $this->task->start_at = Carbon::now();
        $this->task->status = TaskStatus::PROCESSING;
        $this->task->save();

        $uuid = $this->uuid;
        $disk = Storage::disk('public');
        $relative_folder_path = "files/convert/$uuid";
        if (!$disk->exists($relative_folder_path)) {
            $disk->makeDirectory("$relative_folder_path"); //creates directory
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
            $file_path = $this->zipShapefile($disk->path("files/convert/{$uuid}"), $name);
        }

        $this->task->status = TaskStatus::COMPLETED;
        $this->task->end_at = Carbon::now();
        $this->task->save();

        ConvertedFile::create([
            'name' => basename($file_path),
            'path' => $file_path,
            'task_id' => $this->task->id,
        ]);
    }

    public function failed(\Exception $exception): void
    {
        $disk = Storage::disk('public');
        $relative_folder_path = "files/convert/{$this->uuid}";
        if ($disk->exists($relative_folder_path)) {
            $disk->deleteDirectory("$relative_folder_path");
        }

        $this->task->status = TaskStatus::FAILED;
        $this->task->error = $exception->getMessage();
        $this->task->end_at = Carbon::now();
        $this->task->save();
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
