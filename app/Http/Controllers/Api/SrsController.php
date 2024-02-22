<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Library\QueryBuilder\QueryBuilder;
use App\Models\Srs;
use App\Traits\ResponseType;
use Illuminate\Http\Request;

class SrsController extends Controller
{
    use ResponseType;
    public function index(Request $request)
    {
        $query = Srs::query();
        $query = QueryBuilder::for($query, $request)
            ->defaultSort('id')
            ->allowedSearch(['name', 'code', 'description'])
            ->select('id', 'name', 'code', 'description')
            ->allowedPagination();

        return response()->json(new \App\Http\Resources\Items($query->get()), 200, []);
    }
}
