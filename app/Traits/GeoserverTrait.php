<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

/**
 * Trait ResponseType.
 */
trait GeoserverTrait
{
    /**
     * @return mixed
     */
    public function getPrefixUrl()
    {
        $geoserverUrl = config('app.geoserver.uri');
        return "$geoserverUrl/rest";
    }

    public function getClient()
    {
        return Http::withBasicAuth(config('app.geoserver.username'), config('app.geoserver.password'))
            ->withOptions([
                'verify' => false,
            ]);
    }

    public function getGeoserverData($url, $format = 'json')
    {
        $prefixUrl = $this->getPrefixUrl();
        $client = $this->getClient();
        $response = $client->get("$prefixUrl/$url.$format");
        return $response;
    }

    public function postGeoserverData($url, $payload, $contentType = 'json', callable $cb_error = null)
    {
        $prefixUrl = $this->getPrefixUrl();
        $client = $this->getClient();
        if ($contentType == 'json') {
            $response = $this->handleHttp($client->asJson()->post("$prefixUrl/$url", $payload), null, $cb_error);
        } else {
            $response = $client->withHeaders([
                'content-type' => $contentType,
            ])->withBody($payload, $contentType)->put("$prefixUrl/$url");
        }
        return $response;
    }

    public function putGeoserverData($url, $payload, $contentType = 'json')
    {
        $prefixUrl = $this->getPrefixUrl();
        $client = $this->getClient();
        if ($contentType == 'json') {
            $response = $this->handleHttp($client->asJson()->put("$prefixUrl/$url", $payload));
        } else {
            $response = $client->withHeaders([
                'content-type' => $contentType,
            ])->withBody($payload, $contentType)->put("$prefixUrl/$url");
        }
        return $response;
    }

    public function deleteGeoserverData($url)
    {
        $prefixUrl = $this->getPrefixUrl();
        $client = $this->getClient();
        $response = $client->delete("$prefixUrl/$url?recurse=true");
        return $response;
    }

    public function handleHttp($http, $cbSuccessfull = null, $cbError = null)
    {
        $response = $http;
        if ($response->successful()) {
            return isset($cbSuccessfull) ? $cbSuccessfull($response) : $response;
        };
        \Log::channel('geoserver-daily')->debug($response->body());
        if (isset($cbError)) {
            return $cbError($response);
        }
        abort(500, 'Error happend when interacting with geoserver');
    }
}
