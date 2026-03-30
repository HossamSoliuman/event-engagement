<?php

namespace App\Traits;

use Illuminate\Support\Facades\File;

trait ApiResponse
{
    public function apiResponse($data, $message = null, $success = 1, $code = 200)
    {
        return response()->json(
            [
                'success' => $success,
                'data' => $data,
                'message' => $message,
                'code' => $code,
            ],
            $code
        );
    }
}
