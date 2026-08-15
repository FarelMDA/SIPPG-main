@php
    $user = auth('web')->user();
    $isActive = fn (string $pattern) => request()->routeIs($pattern);
@endphp
<aside class="no-print fixed inset-y-0 left-0 z-30 hidden w-[280px] flex-col overflow-y-auto bg-sidebar px-4 py-6 lg:flex">
    <div class="mb-8 flex items-center gap-2 px-2">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 text-lg font-bold text-brand-yellow">S</div>
        <div>
            <p class="text-sm font-semibold leading-none text-white">SI-PPG</p>
            <p class="text-xs text-white/60">Pembinaan Generus</p>
        </div>
    </div>

    <nav class="flex flex-1 flex-col gap-1">
        @can('dashboard.view')
            <a href="{{ route('dashboard') }}" class="sidebar-item" data-active="{{ $isActive('dashboard') ? 'true' : 'false' }}">
                <x-icon name="dashboard" size="18" /> Dashboard
            </a>
        @endcan

        @canany(['struktur-organisasi.manage', 'struktur-organisasi.view', 'kelas.manage', 'kelas.view', 'jenis-kegiatan.manage', 'referensi.manage'])
            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-white/50">Master Data</p>
            @canany(['struktur-organisasi.manage', 'struktur-organisasi.view', 'kelas.manage', 'kelas.view'])
                <a href="{{ route('master-data.struktur-organisasi') }}" class="sidebar-item" data-active="{{ $isActive('master-data.struktur-organisasi') ? 'true' : 'false' }}">
                    <x-icon name="database" size="18" /> Struktur Organisasi
                </a>
            @endcanany
            @canany(['referensi.manage', 'jenis-kegiatan.manage', 'jenis-musyawaroh.manage'])
                <a href="{{ route('master-data.referensi') }}" class="sidebar-item" data-active="{{ $isActive('master-data.referensi') ? 'true' : 'false' }}">
                    <x-icon name="list-checks" size="18" /> Referensi
                </a>
            @endcanany
        @endcanany

        @canany(['sensus.view', 'generus.manage', 'generus.view', 'pendidik.manage', 'pendidik.view', 'import.run'])
            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-white/50">Sensus</p>
            @can('sensus.view')
                <a href="{{ route('sensus') }}" class="sidebar-item" data-active="{{ $isActive('sensus') ? 'true' : 'false' }}">
                    <x-icon name="bar-chart" size="18" /> Sensus Generus
                </a>
            @endcan
            @canany(['generus.manage', 'generus.view'])
                <a href="{{ route('sensus.generus') }}" class="sidebar-item" data-active="{{ $isActive('sensus.generus*') ? 'true' : 'false' }}">
                    <x-icon name="users" size="18" /> Generus
                </a>
            @endcanany
            @canany(['pendidik.manage', 'pendidik.view'])
                <a href="{{ route('sensus.pendidik') }}" class="sidebar-item" data-active="{{ $isActive('sensus.pendidik*') ? 'true' : 'false' }}">
                    <x-icon name="user-circle" size="18" /> Pendidik
                </a>
            @endcanany
            @can('import.run')
                <a href="{{ route('sensus.impor') }}" class="sidebar-item" data-active="{{ $isActive('sensus.impor') ? 'true' : 'false' }}">
                    <x-icon name="upload" size="18" /> Impor Massal
                </a>
            @endcan
        @endcanany

        @canany(['kurikulum.manage', 'kurikulum.view', 'kegiatan.view'])
            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-white/50">Kurikulum</p>
            @canany(['kurikulum.manage', 'kurikulum.view'])
                <a href="{{ route('kurikulum') }}" class="sidebar-item" data-active="{{ $isActive('kurikulum') ? 'true' : 'false' }}">
                    <x-icon name="calendar" size="18" /> Kelola Kurikulum
                </a>
            @endcanany
            @can('kegiatan.view')
                <a href="{{ route('kurikulum.rekap-kbm') }}" class="sidebar-item" data-active="{{ $isActive('kurikulum.rekap-kbm') ? 'true' : 'false' }}">
                    <x-icon name="printer" size="18" /> Rekap KBM Lintas Kelompok
                </a>
            @endcan
        @endcanany

        @can('musyawaroh.manage')
            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-white/50">Musyawaroh</p>
            <a href="{{ route('musyawaroh.index') }}" class="sidebar-item" data-active="{{ $isActive('musyawaroh.*') ? 'true' : 'false' }}">
                <x-icon name="users" size="18" /> Notulen Musyawaroh
            </a>
        @endcan

        @canany(['konseling.manage', 'konseling.view'])
            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-white/50">Konseling</p>
            <a href="{{ route('konseling.index') }}" class="sidebar-item" data-active="{{ $isActive('konseling.*') ? 'true' : 'false' }}">
                <x-icon name="clipboard-check" size="18" /> Catatan Konseling
            </a>
        @endcanany

        @canany(['kegiatan.manage', 'kegiatan.view', 'program-monitoring.manage'])
            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-white/50">Kegiatan</p>
            <a href="{{ route('kegiatan.index') }}" class="sidebar-item" data-active="{{ $isActive('kegiatan.index') || $isActive('kegiatan.form') || $isActive('kegiatan.petugas') || $isActive('kegiatan.rekap') ? 'true' : 'false' }}">
                <x-icon name="party" size="18" /> Daftar Kegiatan
            </a>
            @can('program-monitoring.manage')
                <a href="{{ route('kegiatan.program-monitoring') }}" class="sidebar-item" data-active="{{ $isActive('kegiatan.program-monitoring*') ? 'true' : 'false' }}">
                    <x-icon name="list-checks" size="18" /> Program Monitoring
                </a>
            @endcan
        @endcanany

        @canany(['laporan.manage', 'laporan.review'])
            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-white/50">Laporan Bulanan</p>
            @if($user->hasRole('pjp-kelompok'))
                <a href="{{ route('laporan.kelompok.index') }}" class="sidebar-item" data-active="{{ $isActive('laporan.kelompok.*') || $isActive('laporan.viewer') ? 'true' : 'false' }}">
                    <x-icon name="clipboard-check" size="18" /> Laporan Kelompok
                </a>
            @endif
            @if($user->hasRole('pjp-desa'))
                <a href="{{ route('laporan.desa.index') }}" class="sidebar-item" data-active="{{ $isActive('laporan.desa.*') ? 'true' : 'false' }}">
                    <x-icon name="clipboard-check" size="18" /> Laporan Desa
                </a>
            @endif
            @if($user->hasRole('admin-daerah'))
                <a href="{{ route('laporan.daerah.index') }}" class="sidebar-item" data-active="{{ $isActive('laporan.daerah.*') ? 'true' : 'false' }}">
                    <x-icon name="clipboard-check" size="18" /> Laporan Daerah
                </a>
            @endif
            @can('laporan.review')
                <a href="{{ route('laporan.antrian-approval') }}" class="sidebar-item" data-active="{{ $isActive('laporan.antrian-approval') ? 'true' : 'false' }}">
                    <x-icon name="list-checks" size="18" /> Antrian Approval Laporan
                </a>
                <a href="{{ route('laporan.telusur') }}" class="sidebar-item" data-active="{{ $isActive('laporan.telusur') ? 'true' : 'false' }}">
                    <x-icon name="search" size="18" /> Telusur Laporan
                </a>
            @endcan
        @endcanany

        @canany(['users.manage', 'roles.view', 'roles.manage'])
            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-white/50">Pengaturan</p>
            @can('users.manage')
                <a href="{{ route('pengaturan.pengguna') }}" class="sidebar-item" data-active="{{ $isActive('pengaturan.pengguna*') ? 'true' : 'false' }}">
                    <x-icon name="settings" size="18" /> Kelola Pengguna
                </a>
            @endcan
            @canany(['roles.view', 'roles.manage'])
                <a href="{{ route('pengaturan.matriks-role') }}" class="sidebar-item" data-active="{{ $isActive('pengaturan.matriks-role*') ? 'true' : 'false' }}">
                    <x-icon name="list-checks" size="18" /> Matriks Role &amp; Permission
                </a>
            @endcanany
        @endcanany
    </nav>

    <div class="mt-4 border-t border-white/15 pt-4">
        <a href="{{ route('profil') }}" class="sidebar-item" data-active="{{ $isActive('profil') ? 'true' : 'false' }}">
            <x-icon name="user-circle" size="18" /> Profil Saya
        </a>
    </div>
</aside>
