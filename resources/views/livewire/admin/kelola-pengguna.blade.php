<div>
    <x-page-header title="Kelola Pengguna" description="Akun internal — 14 role dari tingkat Daerah, Desa, hingga Kelompok">
        <x-slot:actions>
            <x-button variant="primary" icon="plus" wire:click="openCreate">Tambah Akun</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Kelompok/Desa</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="font-medium text-ink-primary">{{ $user->nama }}</td>
                        <td>{{ $user->username }}</td>
                        <td><x-badge variant="info">{{ $user->getRoleNames()->first() }}</x-badge></td>
                        <td>{{ $user->kelompok?->nama ?? $user->desa?->nama ?? '—' }}</td>
                        <td>
                            <button type="button" wire:click="toggleActive({{ $user->id }})">
                                <x-badge :variant="$user->is_active ? 'success' : 'neutral'">{{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
                            </button>
                        </td>
                        <td class="text-right">
                            <button type="button" wire:click="openEdit({{ $user->id }})" class="mr-2 text-ink-muted hover:text-brand-primary"><x-icon name="pencil" size="16" /></button>
                            <button type="button" wire:click="resetPassword({{ $user->id }})" wire:confirm="Reset password {{ $user->nama }}? Sesi aktif akun ini akan berakhir." class="text-ink-muted hover:text-warning-solid">Reset Password</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state title="Belum ada pengguna" icon="users" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->users->links() }}</div>

    <x-modal wire:model="showFormModal" :title="$editingId ? 'Edit Akun' : 'Tambah Akun'">
        <form wire:submit="simpan" class="space-y-4">
            <x-input label="Nama Lengkap" name="nama" wire:model="nama" required :error="$errors->first('nama')" />
            <x-input label="Username" name="username" wire:model="username" required :error="$errors->first('username')" />
            <x-select label="Role" name="role" wire:model.live="role" :options="$roleOptions" placeholder="Pilih role" required :error="$errors->first('role')" />

            @if(($scopeLevels[$role] ?? null) === 'kelompok')
                <x-select label="Kelompok" name="kelompok_id" wire:model="kelompok_id" :options="$kelompokOptions" placeholder="Pilih kelompok" required :error="$errors->first('kelompok_id')" />
            @endif

            @if(($scopeLevels[$role] ?? null) === 'desa')
                <x-select label="Desa" name="desa_id" wire:model="desa_id" :options="$desaOptions" placeholder="Pilih desa" required :error="$errors->first('desa_id')" />
            @endif

            @if(in_array($role, ['pjp-desa', 'pjp-kelompok'], true))
                <x-info-banner variant="warning">Role ini adalah Koordinator tunggal — hanya boleh 1 akun aktif per {{ $role === 'pjp-desa' ? 'Desa' : 'Kelompok' }}. Nonaktifkan akun lama dulu bila ingin menggantinya.</x-info-banner>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>

    @if($generatedPassword)
        <x-modal wire:model="generatedPassword" title="Password Baru">
            <div x-data class="space-y-3">
                <p class="text-sm text-ink-secondary">Password untuk <strong>{{ $generatedForNama }}</strong>:</p>
                <div class="flex items-center gap-2 rounded-md border border-border bg-surface-subtle px-3 py-2 font-mono text-lg tracking-wide">
                    {{ $generatedPassword }}
                </div>
                <x-info-banner variant="warning">Password baru ditampilkan sekali saat dibuat/direset — sampaikan langsung ke pengguna, sistem tidak menyimpan salinan yang bisa dilihat ulang.</x-info-banner>
                <div class="flex justify-end">
                    <x-button variant="primary" wire:click="$set('generatedPassword', null)">Tutup</x-button>
                </div>
            </div>
        </x-modal>
    @endif
</div>
