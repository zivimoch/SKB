<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DeveloperPortalController extends Controller
{
    public function index(): View
    {
        return view('developers.index');
    }

    public function apiReference(): View
    {
        return view('developers.api');
    }

    public function openApi(): Response
    {
        return response(file_get_contents(base_path('docs/openapi.yaml')), 200, [
            'Content-Type' => 'application/yaml; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function document(string $path): View
    {
        abort_if(str_contains($path, '..'), 404);
        $base = realpath(base_path('docs'));
        $file = realpath(base_path('docs/'.$path));
        abort_unless($file && str_starts_with($file, $base) && str_ends_with($file, '.md'), 404);

        return view('developers.document', [
            'title' => Str::headline(pathinfo($file, PATHINFO_FILENAME)),
            'content' => Str::markdown(file_get_contents($file), [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ]);
    }
}
