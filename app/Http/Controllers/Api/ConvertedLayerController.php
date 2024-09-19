<?php

namespace App\Http\Controllers\Api;

use App\Constants\OutputType;
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

    public function download(Request $request, string $id)
    {
        $data = ConvertedLayer::findOrFail($id);
        $request->validate([
            'output_type' => 'required',
        ]);
        $base_url = config('app.geoserver.uri') . "/wfs?request=GetFeature&service=WFS&version=1.0.0&typeName={$data->layer_name}";
        if ($request->get('output_type') == OutputType::GEOJSON) {
            $url = $base_url . "&outputFormat=application%2Fjson";
        } elseif ($request->get('output_type') == OutputType::KML) {
            $url = $base_url . "&outputFormat=application/vnd.google-earth.kml+xml";
        } else if ($request->get('output_type') == OutputType::SHAPEFILE) {
            $url = $base_url . "&outputFormat=SHAPE-ZIP";
        } else {
            abort(500, "Output Format is invalid");
        }
        return $url;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
