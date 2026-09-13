<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function dbCheck()
    {
        $start = microtime(true);

        try {
            DB::select('select 1');
            $status = 'ok';
        } catch (\Throwable $e) {
            $status = 'error';
        }

        $ms = round((microtime(true) - $start) * 1000, 2);

        return view('health.db-check', compact('status', 'ms'));
    }
}
