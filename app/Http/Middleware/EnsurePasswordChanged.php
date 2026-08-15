<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SRS §3.3 / UCIC UC-02 — memblokir seluruh rute lain (kecuali logout & rute
 * ganti password itu sendiri) selama must_change_password=true, untuk KEDUA
 * guard (web & orangtua).
 */
class EnsurePasswordChanged
{
    /** Rute yang tetap boleh diakses walau wajib ganti password. */
    private const ALLOWED_ROUTES = [
        'password.ganti',
        'portal.password.ganti',
        'logout',
        'portal.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web') ?? $request->user('orangtua');

        if (! $user) {
            return $next($request);
        }

        if (! $user->must_change_password) {
            return $next($request);
        }

        // Setiap wire:submit/wire:click di GantiPasswordForm (termasuk aksi
        // simpan() itu sendiri) berjalan lewat endpoint AJAX internal Livewire
        // (mis. route name "default.livewire.update"), BUKAN rute halaman
        // "password.ganti" di atas — jadi dicek berdasar path, bukan nama rute.
        // Aman: selama must_change_password=true, satu-satunya halaman yang bisa
        // ter-render di browser adalah GantiPasswordForm sendiri (semua rute GET
        // lain sudah diarahkan ke sini lebih dulu), jadi permintaan AJAX apa pun
        // yang masuk pasti berasal dari komponen ini.
        if ($request->is('livewire/*')) {
            return $next($request);
        }

        if ($request->route() && in_array($request->route()->getName(), self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        $guard = $request->user('orangtua') ? 'orangtua' : 'web';

        return redirect()->route($guard === 'orangtua' ? 'portal.password.ganti' : 'password.ganti');
    }
}
