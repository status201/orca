<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ApiDocsController extends Controller
{
    /**
     * Display the API documentation page with Swagger UI and token management.
     */
    public function index(): View
    {
        return view('api.index');
    }
}
