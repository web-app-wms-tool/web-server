<?php

namespace App\Jobs;

use App\Constants\TaskStatus;
use App\Models\Task;
use App\Models\UploadedFile;
use Carbon\Carbon;
use File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Process;
use proj4php\Point;
use proj4php\Proj4php;
use proj4php\Proj;
use Storage;

class HandleReadFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 0;
    /**
     * Create a new job instance.
     */

    protected $data;
    protected $task;
    public function __construct(UploadedFile $data, Task $task)
    {
        $this->data = $data;
        $this->task = $task;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->task->start_at = Carbon::now();
        $this->task->status = TaskStatus::PROCESSING;
        $this->task->save();

        $file_extension = File::extension($this->data->path);

        $disk = Storage::disk('public');
        if ($file_extension == "dwg") {
            $dxf_path = $this->convertDWG2DXF($disk->path('files/upload/' . $this->data->uuid));
            $this->data->dxf_path = $dxf_path;
        }

        $extent = $this->getExtent($this->data->dxf_path);
        $layers = $this->getLayers($this->data->dxf_path);
        $geometry_types = $this->getGeometryType($this->data->dxf_path);
        $this->data->is_read_done = 1;

        $proj4 = new Proj4php();

        $s_proj4 = new Proj($this->data->srs, $proj4);
        $t_proj4 = new Proj("EPSG:6991", $proj4);

        $s_min_point = new Point($extent[0], $extent[1], $s_proj4);
        $t_min_point = $proj4->transform($t_proj4, $s_min_point);

        $s_max_point = new Point($extent[2], $extent[3], $s_proj4);
        $t_max_point = $proj4->transform($t_proj4, $s_max_point);

        $this->data->metadata = [
            "bbox" => [
                $t_min_point->__get("x"),
                $t_min_point->__get("y"),
                $t_max_point->__get("x"),
                $t_max_point->__get("y"),
            ],
            'layers' => $layers,
            'geometry_types' => $geometry_types,
        ];
        $this->data->save();

        $this->task->status = TaskStatus::COMPLETED;
        $this->task->end_at = Carbon::now();
        $this->task->save();

    }
    public function failed(\Exception $exception): void
    {
        $this->task->status = TaskStatus::FAILED;
        $this->task->error = $exception->getMessage();
        $this->task->end_at = Carbon::now();
        $this->task->save();
    }

    private function getExtent(string $file_path): array
    {
        $cmd = config('tool.ogrinfo_path');
        $cmd .= " -al -so {$file_path}";
        $process = Process::run($cmd);
        $output = $process->output();
        if ($process->failed()) {
            throw new \Exception($output);
        }
        $output = preg_split("/\r\n|\n|\r/", $output);
        $extent = array_values(array_filter($output, function ($item) {
            $item = mb_strtolower($item);
            if (str_contains($item, "extent:")) {
                return true;
            }
            return false;

        }))[0];
        $extent = mb_strtolower($extent);
        $extent = str_replace("extent: ", "", $extent);
        $extent = str_replace("(", "", $extent);
        $extent = str_replace(")", "", $extent);
        $extent = str_replace(" - ", ", ", $extent);
        $extent = explode(", ", $extent);
        $extent = array_map(function ($item) {
            return (float) $item;
        }, $extent);
        return $extent;
    }

    private function getLayers(string $file_path): array
    {
        $cmd = config('tool.ogrinfo_path');
        $cmd .= " -sql \"SELECT DISTINCT layer FROM entities\"";
        $cmd .= " $file_path";
        $process = Process::run($cmd);
        $output = $process->output();
        if ($process->failed()) {
            throw new \Exception($output);
        }
        $output = preg_split("/\r\n|\n|\r/", $output);
        $layers = array_values(array_filter($output, function ($item) {
            $item = mb_strtolower($item);
            if (str_contains($item, "layer (string)")) {
                return true;
            }
            return false;
        }));
        $layers = array_map(function ($layer) {
            return str_replace("  layer (String) = ", "", $layer);
        }, $layers);

        return $layers;
    }

    private function getGeometryType(string $file_path): array
    {
        $cmd = config('tool.ogrinfo_path');
        $cmd .= " -sql \"SELECT DISTINCT ogr_geometry FROM entities\"";
        $cmd .= " $file_path";
        $process = Process::run($cmd);
        $output = $process->output();
        if ($process->failed()) {
            throw new \Exception($output);
        }
        $output = preg_split("/\r\n|\n|\r/", $output);
        $geometry_types = array_values(array_filter($output, function ($item) {
            $item = mb_strtolower($item);
            if (str_contains($item, "ogr_geometry (string)")) {
                return true;
            }
            return false;
        }));
        $geometry_types = array_map(function ($geometry_type) {
            return str_replace("  ogr_geometry (String) = ", "", $geometry_type);
        }, $geometry_types);

        return $geometry_types;
    }

    private function convertDWG2DXF(string $path): string
    {
        $cmd = config('tool.teigha_path');
        $cmd .= " $path $path";
        $cmd .= ' ACAD2018 DXF 0 1 "*.DWG"';
        $process = Process::run($cmd);
        if ($process->failed()) {
            $error = $process->output();
            if (empty($error)) {
                $error = "Can not convert dwg to dxf";
            }
            throw new \Exception($error);
        }

        $files = File::files($path);
        try {
            $dxf_file = array_values(array_filter($files, function ($file) {
                if ($file->getExtension() == 'dxf') {
                    return true;
                }
                return false;
            }))[0];
        } catch (\Exception $e) {
            throw new \Exception("File not exist");
        }

        return $dxf_file->getRealPath();
    }
}
