<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/**
 * One-time, password-gated schema deploy for hosting with no shell access
 * (Vercel). Runs `migrate` and, once, `db:seed`.
 *
 * DELETE THIS FILE, its route, and the view once you've used it — a
 * migration/seed trigger reachable at a public URL is a standing risk even
 * behind a password: it bypasses the app's normal auth entirely, the
 * password can leak via logs or shoulder-surfing, and there's no rate
 * limiting here to stop someone from brute-forcing it.
 */
class DeployController extends Controller
{
    private const PASSWORD_HASH = '$2y$12$rIchqptz2fuAZyBDXeEtU.J9aPXXHQUS1R1ois8GNQEvxk3UUwIn.';

    public function handle(Request $request)
    {
        $error  = null;
        $output = null;

        if ($request->isMethod('post')) {
            if (! Hash::check((string) $request->input('password'), self::PASSWORD_HASH)) {
                $error = 'Incorrect password.';
            } else {
                $output = $this->run();
            }
        }

        return response()->view('system.deploy', compact('error', 'output'));
    }

    private function run(): string
    {
        $log = "== php artisan migrate --force ==\n";
        Artisan::call('migrate', ['--force' => true]);
        $log .= Artisan::output();

        $log .= "\n== php artisan db:seed --force ==\n";
        if (Project::where('project_no', 'PRJ-2026-001')->exists()) {
            $log .= "Skipped — demo data already present (PRJ-2026-001 exists). Delete this route now.\n";
        } else {
            Artisan::call('db:seed', ['--force' => true]);
            $log .= Artisan::output();
        }

        return $log;
    }
}
