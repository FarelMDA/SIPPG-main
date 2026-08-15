<?php

namespace App\Livewire\Profil;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * P-PROF-01 — Profil & Ganti Password (UIUX §3.2). Bukan use case tersendiri di
 * UCIC — murni halaman pembungkus Auth\GantiPasswordForm (UC-02) + info akun.
 */
#[Layout('layouts.app')]
class ProfilSaya extends Component
{
    public function render()
    {
        return view('livewire.profil.profil-saya', [
            'user' => Auth::guard('web')->user(),
        ]);
    }
}
