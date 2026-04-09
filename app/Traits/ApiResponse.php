<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data, int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'data'    => $data,
        ];

        if (is_array($data)) {
            $payload['total'] = count($data);
        }

        return response()->json($payload, $status);
    }

    protected function error(string $message, int $status, ?string $detail = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'error'   => $message,
        ];

        if ($detail) {
            $payload['detail'] = $detail;
        }

        return response()->json($payload, $status);
    }
}