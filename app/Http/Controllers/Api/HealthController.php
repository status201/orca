<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $database = 'ok';
        } catch (\Throwable) {
            $database = 'error';
        }

        $status = $database === 'ok' ? 'ok' : 'error';
        $code = $status === 'ok' ? 200 : 503;

        return response()->json([
            'status' => $status,
            'database' => $database,
        ], $code);
    }
}
