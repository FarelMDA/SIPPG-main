<?php

use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\ValidasiTokenPetugasPresensi;
use App\Jobs\HitungSensusSnapshotBulanan;
use App\Jobs\SinkronisasiHariLiburGoogle;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnsurePasswordChanged::class,
        ]);

        $middleware->alias([
            'token.kegiatan' => ValidasiTokenPetugasPresensi::class,
        ]);

        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(new HitungSensusSnapshotBulanan)->monthlyOn(1, '01:00');
        $schedule->job(new SinkronisasiHariLiburGoogle)->monthlyOn(1, '02:00');
        $schedule->command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
    })
    ->create();
