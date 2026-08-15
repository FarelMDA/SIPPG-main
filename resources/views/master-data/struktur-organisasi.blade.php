<x-layouts::app title="Struktur Organisasi">
    @php
        $defaultTab = auth('web')->user()?->can('struktur-organisasi.view') ? 'desa' : 'kelas';
    @endphp
    <div x-data="{ tab: '{{ $defaultTab }}' }">
        <x-page-header title="Kelola Struktur Organisasi" description="Desa, Kelompok (KBM), Jenjang, dan Kelas" />

        <div class="no-print mb-6 flex gap-1 border-b border-border">
            <button type="button" @click="tab = 'desa'" :class="tab === 'desa' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-ink-muted'" class="border-b-2 px-4 py-2 text-sm font-medium">Desa</button>
            <button type="button" @click="tab = 'kelompok'" :class="tab === 'kelompok' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-ink-muted'" class="border-b-2 px-4 py-2 text-sm font-medium">Kelompok</button>
            <button type="button" @click="tab = 'jenjang'" :class="tab === 'jenjang' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-ink-muted'" class="border-b-2 px-4 py-2 text-sm font-medium">Jenjang</button>
            <button type="button" @click="tab = 'kelas'" :class="tab === 'kelas' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-ink-muted'" class="border-b-2 px-4 py-2 text-sm font-medium">Kelas</button>
        </div>

        <div x-show="tab === 'desa'" @if($defaultTab !== 'desa') x-cloak @endif>
            @can('struktur-organisasi.view')
                <livewire:master-data.kelola-desa />
            @else
                <x-empty-state title="Anda tidak memiliki akses untuk mengelola Desa" />
            @endcan
        </div>
        <div x-show="tab === 'kelompok'" x-cloak>
            @can('struktur-organisasi.view')
                <livewire:master-data.kelola-kelompok />
            @else
                <x-empty-state title="Anda tidak memiliki akses untuk mengelola Kelompok" />
            @endcan
        </div>
        <div x-show="tab === 'jenjang'" x-cloak>
            @can('referensi.manage')
                <livewire:master-data.kelola-referensi :model="\App\Models\Jenjang::class" judul="Jenjang" :with-kategori-usia="true" wire:key="struktur-jenjang" />
            @else
                <x-empty-state title="Anda tidak memiliki akses untuk mengelola Jenjang" />
            @endcan
        </div>
        <div x-show="tab === 'kelas'" @if($defaultTab !== 'kelas') x-cloak @endif>
            @can('kelas.view')
                <livewire:master-data.kelola-kelas />
            @else
                <x-empty-state title="Anda tidak memiliki akses untuk mengelola Kelas" />
            @endcan
        </div>
    </div>
</x-layouts::app>
