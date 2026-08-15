<div>
    <x-page-header title="Akun Portal Orang Tua" description="Reset password akun orang tua yang lupa kredensial" />

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nomor HP</th>
                    <th>Jumlah Anak Tertaut</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->akun as $akun)
                    <tr wire:key="ortu-{{ $akun->id }}">
                        <td class="font-medium text-ink-primary">{{ $akun->nomor_hp }}</td>
                        <td>{{ $akun->generus_count }}</td>
                        <td><x-badge :variant="$akun->is_active ? 'success' : 'neutral'">{{ $akun->is_active ? 'Aktif' : 'Tidak Aktif' }}</x-badge></td>
                        <td class="text-right">
                            <button type="button" wire:click="resetPassword({{ $akun->id }})" wire:confirm="Reset password akun {{ $akun->nomor_hp }}?" class="text-ink-muted hover:text-warning-solid">Reset Password</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty-state title="Belum ada akun orang tua" icon="users" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->akun->links() }}</div>

    @if($generatedPassword)
        <x-modal wire:model="generatedPassword" title="Password Baru">
            <div class="space-y-3">
                <p class="text-sm text-ink-secondary">Password untuk akun <strong>{{ $generatedForNomorHp }}</strong>:</p>
                <div class="rounded-md border border-border bg-surface-subtle px-3 py-2 font-mono text-lg tracking-wide">{{ $generatedPassword }}</div>
                <x-info-banner variant="warning">Password baru ditampilkan sekali saat dibuat/direset — sampaikan langsung ke pengguna.</x-info-banner>
                <div class="flex justify-end">
                    <x-button variant="primary" wire:click="$set('generatedPassword', null)">Tutup</x-button>
                </div>
            </div>
        </x-modal>
    @endif
</div>
