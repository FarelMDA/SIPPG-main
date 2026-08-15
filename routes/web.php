<?php

use App\Http\Controllers\MasterData\ImporTemplateController;
use App\Livewire\Admin\KelolaAkunOrangTua;
use App\Livewire\Admin\KelolaPengguna;
use App\Livewire\Admin\MatriksRolePermission;
use App\Livewire\Auth\GantiPasswordForm;
use App\Livewire\Auth\LoginForm;
use App\Livewire\Auth\LoginOrangTuaForm;
use App\Livewire\Dashboard\DashboardKelompok;
use App\Livewire\Kegiatan\FormJadwalKegiatan;
use App\Livewire\Kegiatan\FormKegiatan;
use App\Livewire\Kegiatan\InputPresensiKegiatan;
use App\Livewire\Kegiatan\KelolaHariLibur;
use App\Livewire\Kegiatan\KelolaJadwalKegiatan;
use App\Livewire\Kegiatan\KelolaKegiatan;
use App\Livewire\Kegiatan\KelolaPetugasPresensi;
use App\Livewire\Kegiatan\KelolaProgramMonitoring;
use App\Livewire\Kegiatan\RekapKegiatan;
use App\Livewire\Kegiatan\RekapProgramKegiatan;
use App\Livewire\Konseling\KelolaKonseling;
use App\Livewire\Kurikulum\KelolaKurikulum;
use App\Livewire\Kurikulum\RekapKbmLintasKelompok;
use App\Livewire\Laporan\AntrianApprovalLaporan;
use App\Livewire\Laporan\DaftarLaporanBerjenjang;
use App\Livewire\Laporan\GenerateLaporanDaerah;
use App\Livewire\Laporan\GenerateLaporanDesa;
use App\Livewire\Laporan\GenerateLaporanKelompok;
use App\Livewire\Laporan\ViewerLaporanSlide;
use App\Livewire\MasterData\ImporMassal;
use App\Livewire\MasterData\KelolaGenerus;
use App\Livewire\MasterData\KelolaPendidik;
use App\Livewire\Musyawaroh\KelolaMusyawaroh;
use App\Livewire\PortalOrangTua\Dashboard as PortalDashboard;
use App\Livewire\PortalOrangTua\LihatJurnal;
use App\Livewire\PortalOrangTua\LihatPresensi as PortalLihatPresensi;
use App\Livewire\PortalOrangTua\NotifikasiFeed;
use App\Livewire\Profil\ProfilSaya;
use App\Livewire\Sensus\SensusDashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guard `web` — Aplikasi Internal (Admin Daerah, PJP Desa/Kelompok,
| Sekretaris KBM, Guru). SRS §2.1, §6.
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/login');

Route::middleware('guest:web')->group(function () {
    Route::get('/login', LoginForm::class)->name('login');
});

Route::post('/logout', function () {
    Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth:web')->name('logout');

Route::middleware('auth:web')->group(function () {
    Route::get('/ganti-password', GantiPasswordForm::class)->name('password.ganti');

    Route::get('/dashboard', DashboardKelompok::class)->name('dashboard');

    Route::get('/master-data/struktur-organisasi', function () {
        return view('master-data.struktur-organisasi');
    })->name('master-data.struktur-organisasi');
    Route::get('/master-data/referensi', function () {
        return view('master-data.referensi');
    })->name('master-data.referensi');

    Route::get('/sensus', SensusDashboard::class)->name('sensus');
    Route::get('/sensus/generus', KelolaGenerus::class)->name('sensus.generus');
    Route::get('/sensus/pendidik', KelolaPendidik::class)->name('sensus.pendidik');
    Route::get('/sensus/impor', ImporMassal::class)->name('sensus.impor');
    Route::get('/sensus/impor/template/{tipe}', ImporTemplateController::class)->name('sensus.impor.template');

    Route::get('/kurikulum', KelolaKurikulum::class)->name('kurikulum');
    Route::get('/kurikulum/rekap-kbm', RekapKbmLintasKelompok::class)->name('kurikulum.rekap-kbm');

    Route::get('/musyawaroh', KelolaMusyawaroh::class)->name('musyawaroh.index');
    Route::get('/konseling', KelolaKonseling::class)->name('konseling.index');

    Route::get('/kegiatan', KelolaKegiatan::class)->name('kegiatan.index');
    Route::get('/kegiatan/tambah', FormKegiatan::class)->name('kegiatan.tambah');
    Route::get('/kegiatan/jadwal', KelolaJadwalKegiatan::class)->name('kegiatan.jadwal.index');
    Route::get('/kegiatan/jadwal/tambah', FormJadwalKegiatan::class)->name('kegiatan.jadwal.tambah');
    Route::get('/kegiatan/jadwal/{kegiatanJadwal}/ubah', FormJadwalKegiatan::class)->name('kegiatan.jadwal.ubah');
    Route::get('/kegiatan/hari-libur', KelolaHariLibur::class)->name('kegiatan.hari-libur');
    Route::get('/kegiatan/program/rekap', RekapProgramKegiatan::class)->name('kegiatan.program.rekap');
    Route::get('/kegiatan/program-monitoring', KelolaProgramMonitoring::class)->name('kegiatan.program-monitoring');
    Route::get('/kegiatan/{kegiatan}/ubah', FormKegiatan::class)->name('kegiatan.ubah');
    Route::get('/kegiatan/{kegiatan}/petugas-presensi', KelolaPetugasPresensi::class)->name('kegiatan.petugas');
    Route::get('/kegiatan/{kegiatan}/presensi/{kelompok}', InputPresensiKegiatan::class)->name('kegiatan.presensi');
    Route::get('/kegiatan/{kegiatan}/rekap', RekapKegiatan::class)->name('kegiatan.rekap');

    Route::get('/laporan/kelompok', GenerateLaporanKelompok::class)->name('laporan.kelompok.index');
    Route::get('/laporan/desa', GenerateLaporanDesa::class)->name('laporan.desa.index');
    Route::get('/laporan/daerah', GenerateLaporanDaerah::class)->name('laporan.daerah.index');
    Route::get('/laporan/telusur', DaftarLaporanBerjenjang::class)->name('laporan.telusur');
    Route::get('/laporan/antrian-approval', AntrianApprovalLaporan::class)->name('laporan.antrian-approval');
    Route::get('/laporan/{laporan}', ViewerLaporanSlide::class)->name('laporan.viewer');

    Route::get('/pengaturan/pengguna', KelolaPengguna::class)->name('pengaturan.pengguna');
    Route::get('/pengaturan/akun-orang-tua', KelolaAkunOrangTua::class)->name('pengaturan.akun-orang-tua');
    Route::get('/pengaturan/matriks-role', MatriksRolePermission::class)->name('pengaturan.matriks-role');

    Route::get('/profil', ProfilSaya::class)->name('profil');
});

/*
|--------------------------------------------------------------------------
| Guard `orangtua` — Portal Orang Tua. SRS §12, §2.1.
|--------------------------------------------------------------------------
*/

Route::prefix('portal')->group(function () {
    Route::middleware('guest:orangtua')->group(function () {
        Route::get('/login', LoginOrangTuaForm::class)->name('portal.login');
    });

    Route::post('/logout', function () {
        Auth::guard('orangtua')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('portal.login');
    })->middleware('auth:orangtua')->name('portal.logout');

    Route::middleware('auth:orangtua')->group(function () {
        Route::get('/ganti-password', GantiPasswordForm::class)->name('portal.password.ganti');
        Route::get('/dashboard', PortalDashboard::class)->name('portal.dashboard');
        Route::get('/presensi', PortalLihatPresensi::class)->name('portal.presensi');
        Route::get('/jurnal', LihatJurnal::class)->name('portal.jurnal');
        Route::get('/notifikasi', NotifikasiFeed::class)->name('portal.notifikasi');
    });
});

/*
|--------------------------------------------------------------------------
| Jalur ketiga — TANPA guard, khusus Petugas Presensi Kegiatan berupa
| Generus (SRS §2.1, §18.2, UIUX §3.3.3).
|--------------------------------------------------------------------------
*/

Route::middleware('token.kegiatan')
    ->get('/kegiatan/presensi/{token}', InputPresensiKegiatan::class)
    ->name('kegiatan.presensi.token');
