<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

class Items extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if ($this->resource instanceof LengthAwarePaginator) {
            $result = [
                'list' => $this->collection,
                'pagination' => [
                    'count' => $this->count(),
                    'hasMoreItems' => $this->hasMorePages(),
                    'page' => $this->currentPage(),
                    'total' => $this->total(),
                    'totalPage' => $this->lastPage(),
                    'itemsPerPage' => (float) $this->perPage(),
                ],
            ];
        } else if ($this->resource instanceof Paginator) {
            $result = [
                'list' => $this->collection,
                'pagination' => [
                    'count' => $this->count(),
                    'hasMoreItems' => $this->hasMorePages(),
                    'page' => $this->currentPage(),
                    'itemsPerPage' => (float) $this->perPage(),
                ],
            ];
        } else {
            $result = $this->collection;
        }
        return $result;
    }
}
