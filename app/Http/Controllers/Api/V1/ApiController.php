<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected function ok(mixed $data, array $meta = []): JsonResponse
    {
        return response()->json(array_merge(['data' => $data], $meta ? ['meta' => $meta] : []));
    }

    protected function perPage(Request $request, int $default = 25): int
    {
        return max(1, min($request->integer('per_page', $default), 100));
    }
}
