<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class DocsController extends Controller
{
    public function index()
    {
        $yaml = file_get_contents(base_path('docs/openapi.yaml'));

        return response($yaml, 200, [
            'Content-Type' => 'application/yaml; charset=utf-8',
        ]);
    }
}
