@props(['title' => 'SI-PPG'])
@php
    $user = auth('web')->user();
    $roleLabels = [
        'admin-daerah' => 'Admin Daerah',
        'pjp-desa' => 'PJP Desa',
        'pjp-kelompok' => 'PJP Kelompok',
        'sekretaris-kbm' => 'Sekretaris KBM',
        'guru' => 'Guru',
    ];
    $roleName = $user?->getRoleNames()->first();
@endphp
<header class="no-print sticky top-0 z-20 flex h-[72px] items-center justify-between bg-topbar px-4 shadow-sm lg:pl-[304px] lg:pr-8">
    <div class="flex items-center gap-3 text-white">
        <button type="button" class="lg:hidden" x-data @click="$dispatch('toggle-sidebar-mobile')">
            <x-icon name="menu" size="22" />
        </button>
        <h2 class="text-lg font-semibold">{{ $title }}</h2>
    </div>

    <div x-data="{ open: false }" class="relative">
        <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-white">
            <x-icon name="user-circle" size="20" />
            <span class="hidden text-sm font-medium sm:inline">{{ $user?->nama }}</span>
            @if($roleName)
                <span class="hidden rounded-full bg-white/20 px-2 py-0.5 text-xs sm:inline">{{ $roleLabels[$roleName] ?? $roleName }}</span>
            @endif
            <x-icon name="chevron-down" size="16" />
        </button>

        <div x-show="open" @click.outside="open = false" x-cloak x-transition
             class="absolute right-0 mt-2 w-48 rounded-lg border border-border-subtle bg-white py-1 shadow-md">
            <a href="{{ route('profil') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-ink-primary hover:bg-surface-subtle">
                <x-icon name="user-circle" size="16" /> Profil &amp; Ganti Password
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-danger-text hover:bg-danger-bg">
                    <x-icon name="log-out" size="16" /> Keluar
                </button>
            </form>
        </div>
    </div>
</header>
