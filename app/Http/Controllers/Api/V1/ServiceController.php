<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends ApiController
{
    public function index(Request $request)
    {
        $services = auth()->user()->services()
            ->with(['product', 'plan'])
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate($this->perPage($request));

        return $this->ok($services->items(), ['pagination' => [
            'total' => $services->total(),
            'per_page' => $services->perPage(),
            'current_page' => $services->currentPage(),
            'last_page' => $services->lastPage(),
        ]]);
    }

    public function show(Service $service)
    {
        abort_unless($service->user_id === auth()->id(), 403);

        return $this->ok($service->load(['product', 'plan', 'server']));
    }
}
