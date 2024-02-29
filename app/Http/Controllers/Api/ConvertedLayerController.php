<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Library\QueryBuilder\QueryBuilder;
use App\Models\ConvertedLayer;
use App\Traits\ResponseType;
use Illuminate\Http\Request;

class ConvertedLayerController extends Controller
{
    use ResponseType;
    /**
     * Display a listing of the resource.
     */
    public function indexAgGrid(Request $request)
    {
        $query = ConvertedLayer::query();
        $query = QueryBuilder::for($query, $request)
            ->allowedAgGrid([])
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
