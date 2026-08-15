<x-layouts::app title="Referensi">
    <div x-data="{ tab: 'jenis-kelamin' }">
        <x-page-header title="Kelola Referensi" description="Data acuan global dipakai lintas modul: Jenis Kelamin, Status Domisili, Jenis Pendidik, Status Presensi, Jenis Kegiatan, Jenis Musyawaroh" />

        <div class="no-print mb-6 flex flex-wrap gap-1 border-b border-border">
            <button type="button" @click="tab = 'jenis-kelamin'" :class="tab === 'jenis-kelamin' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-ink-muted'" class="border-b-2 px-4 py-2 text-sm font-medium">Jenis Kelamin</button>
            <button type="button" @click="tab = 'status-domisili'" :class="tab === 'status-domisili' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-ink-muted'" class="border-b-2 px-4 py-2 text-sm font-medium">Status Domisili</button>
            <button type="button" @click="tab = 'jenis-pendidik'" :class="tab === 'jenis-pendidik' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-ink-muted'" class="border-b-2 px-4 py-2 text-sm font-medium">Jenis Pendidik</button>
            <button type="button" @click="tab = 'status-presensi'" :class="tab === 'status-presensi' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-ink-muted'" class="border-b-2 px-4 py-2 text-sm font-medium">Status Presensi</button>
            <button type="button" @click="tab = 'jenis-kegiatan'" :class="tab === 'jenis-kegiatan' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-ink-muted'" class="border-b-2 px-4 py-2 text-sm font-medium">Jenis Kegiatan</button>
            <button type="button" @click="tab = 'jenis-musyawaroh'" :class="tab === 'jenis-musyawaroh' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-ink-muted'" class="border-b-2 px-4 py-2 text-sm font-medium">Jenis Musyawaroh</button>
        </div>

        <div x-show="tab === 'jenis-kelamin'">
            <livewire:master-data.kelola-referensi :model="\App\Models\JenisKelamin::class" judul="Jenis Kelamin" wire:key="referensi-jenis-kelamin" />
        </div>
        <div x-show="tab === 'status-domisili'" x-cloak>
            <livewire:master-data.kelola-referensi :model="\App\Models\StatusDomisili::class" judul="Status Domisili" wire:key="referensi-status-domisili" />
        </div>
        <div x-show="tab === 'jenis-pendidik'" x-cloak>
            <livewire:master-data.kelola-referensi :model="\App\Models\JenisPendidik::class" judul="Jenis Pendidik" wire:key="referensi-jenis-pendidik" />
        </div>
        <div x-show="tab === 'status-presensi'" x-cloak>
            <livewire:master-data.kelola-referensi :model="\App\Models\StatusPresensi::class" judul="Status Presensi" wire:key="referensi-status-presensi" />
        </div>
        <div x-show="tab === 'jenis-kegiatan'" x-cloak>
            @can('jenis-kegiatan.manage')
                <livewire:master-data.kelola-jenis-kegiatan wire:key="referensi-jenis-kegiatan-list" />
            @else
                <x-empty-state title="Anda tidak memiliki akses untuk mengelola Jenis Kegiatan" />
            @endcan
        </div>
        <div x-show="tab === 'jenis-musyawaroh'" x-cloak>
            @can('jenis-musyawaroh.manage')
                <livewire:master-data.kelola-jenis-musyawaroh wire:key="referensi-jenis-musyawaroh-list" />
            @else
                <x-empty-state title="Anda tidak memiliki akses untuk mengelola Jenis Musyawaroh" />
            @endcan
        </div>
    </div>
</x-layouts::app>
