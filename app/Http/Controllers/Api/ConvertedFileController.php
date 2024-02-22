<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Library\QueryBuilder\QueryBuilder;
use App\Models\ConvertedFile;
use App\Traits\ResponseType;
use Illuminate\Http\Request;

class ConvertedFileController extends Controller
{
    use ResponseType;
    /**
     * Display a listing of the resource.
     */
    public function indexAgGrid(Request $request)
    {
        $query = ConvertedFile::query();
        $query = QueryBuilder::for($query, $request)
            ->allowedAgGrid([])
            ->defaultSorts(['id', 'created_at'])
            ->allowedPagination();
        return response()->json(new \App\Http\Resources\Items($query->get()), 200, []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
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
