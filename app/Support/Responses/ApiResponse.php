<?php

namespace App\Support\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        array $meta = [],
        int $status = 200,
    ): JsonResponse {
        return response()->json([
            'data' => $data,
            'meta' => $meta,
            'errors' => null,
        ], $status);
    }

    public static function error(
        string $message,
        array $fields = [],
        int $status = 400,
        array $meta = [],
    ): JsonResponse {
        return response()->json([
            'data' => null,
            'meta' => $meta,
            'errors' => [
                'message' => $message,
                'fields' => $fields,
            ],
        ], $status);
    }
}
