<div>
    <x-page-header title="Profil Saya" />

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card>
            <h2 class="mb-4 text-lg font-semibold text-ink-primary">Informasi Akun</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-ink-muted">Nama</dt><dd class="font-medium">{{ $user->nama }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Username</dt><dd class="font-medium">{{ $user->username }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Role</dt><dd><x-badge variant="info">{{ $user->getRoleNames()->first() }}</x-badge></dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Kelompok</dt><dd class="font-medium">{{ $user->kelompok?->nama ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card>
            <h2 class="mb-4 text-lg font-semibold text-ink-primary">Ganti Password</h2>
            <livewire:auth.ganti-password-form />
        </x-card>
    </div>
</div>
