<?php

namespace App\Http\Controllers\Api;

use App\Constants\TaskStatus;
use App\Constants\TaskType;
use App\Http\Controllers\Controller;
use App\Jobs\HandleConvertFileJob;
use App\Jobs\HandleReadFileJob;
use App\Library\QueryBuilder\QueryBuilder;
use App\Models\Task;
use App\Models\UploadedFile;
use App\Traits\ResponseType;
use App\Traits\Uuid;
use Illuminate\Http\Request;
use Storage;

class UploadedFileController extends Controller
{
    use ResponseType;
    /**
     * Display a listing of the resource.
     */
    public function indexAgGrid(Request $request)
    {
        $query = UploadedFile::query();
        $query = QueryBuilder::for($query, $request)
            ->allowedAgGrid([])
            ->defaultSorts(['-id'])
            ->allowedPagination();
        return response()->json(new \App\Http\Resources\Items($query->get()), 200, []);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file.*' => 'mimetypes:application/octet-stream',
            'srs' => 'required',
        ]);
        $file = $request->file('file');

        $uuid = Uuid::generateUuid();
        $disk = Storage::disk('public');
        $relative_folder_path = "files/upload/$uuid";
        $file_name = $file->getClientOriginalName();
        $file_name = str_replace(" ", "_", $file_name);
        $relative_file_path = $relative_folder_path . "/$file_name";
        if (!$disk->exists("$relative_folder_path")) {
            $disk->makeDirectory("$relative_folder_path"); //creates directory
        }
        $file->move($disk->path("$relative_folder_path"), $file_name);

        $data = UploadedFile::create([
            'name' => $file->getClientOriginalName(),
            'path' => $disk->path($relative_file_path),
            'dxf_path' => $disk->path($relative_file_path),
            'srs' => $request->get('srs'),
            'size' => $disk->size($relative_file_path),
            'uuid' => $uuid,
        ]);

        $task = Task::create([
            'name' => "Reading {$data->name} - " . time(),
            'status' => TaskStatus::CREATED,
            'task_type' => TaskType::READING,
        ]);
        $data->task_id = $task->id;
        $data->save();

        HandleReadFileJob::dispatch($data, $task);

        return $this->responseCreated($data);
    }

    public function convert(string $id, Request $request)
    {
        $data = UploadedFile::findOrFail($id);
        $task = Task::create([
            'name' => "Converting {$data->name} - " . time(),
            'status' => TaskStatus::CREATED,
            'task_type' => TaskType::CONVERTING,
        ]);

        HandleConvertFileJob::dispatch($data, $task, $request->all());
        return $this->responseCreated($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
