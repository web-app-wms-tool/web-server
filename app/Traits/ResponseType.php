<?php

namespace App\Traits;

/**
 * Trait ResponseType.
 */
trait ResponseType
{
    public function responseSuccess($data = [], $message = null)
    {
        $message = empty($message) ? trans('response.200') : $message;

        return response()->json([
            'data' => $data,
            'message' => $message,
        ], 200);
    }
    public function responseError($data = [], $message = null)
    {
        $message = empty($message) ? trans('response.400') : $message;

        return response()->json([
            'data' => $data,
            'message' => $message,
        ], 400);
    }

    public function responseServerError($message = null)
    {
        $message = empty($message) ? 'Response not success' : $message;

        abort(500, $message);
    }

    public function responseBadMethod($message = null)
    {
        $message = empty($message) ? trans('response.400') : $message;
        abort(400, $message);
    }


    public function responseCreated($data = null)
    {
        return $this->responseSuccess($data, trans('response.created-success'));
    }

    public function responseUpdated($data = null)
    {
        return $this->responseSuccess($data, trans('response.updated-success'));
    }

    public function responseDeleted($data = null)
    {
        return $this->responseSuccess($data, trans('response.deleted-success'));
    }
}
