<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Library\QueryBuilder\QueryBuilder;
use App\Models\Task;
use App\Traits\ResponseType;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use ResponseType;
    /**
     * Display a listing of the resource.
     */
    public function indexAgGrid(Request $request)
    {
        $query = Task::query();
        $query = QueryBuilder::for($query, $request)
            ->allowedAgGrid([])
            ->allowedIncludes(['uploaded_file', 'converted_file'])
            ->defaultSorts(['-id'])
            ->allowedPagination();
        return response()->json(new \App\Http\Resources\Items($query->get()), 200, []);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
