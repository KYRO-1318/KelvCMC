<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function ok(mixed $data, array $meta = []): JsonResponse
    {
        return response()->json(array_merge(['data' => $data], $meta ? ['meta' => $meta] : []));
    }
}
