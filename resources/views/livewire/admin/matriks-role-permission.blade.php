<div>
    <x-page-header title="Matriks Role & Permission" :description="$bisaKelola ? 'Klik sel untuk mengubah hak akses per role — dibaca & disimpan langsung ke database' : 'Referensi hak akses per role — read-only, dibaca langsung dari database'" />

    @if($bisaKelola)
        <x-info-banner variant="warning">Perubahan di sini disimpan ke database, tapi akan <strong>ditimpa balik</strong> begitu <code>RolePermissionSeeder</code> dijalankan ulang (mis. saat deploy) — kalau perubahan ini ingin permanen, salin juga ke <code>database/seeders/RolePermissionSeeder.php</code>. Role <code>sysadmin</code> tidak bisa diubah dari sini.</x-info-banner>
    @endif

    <div class="card mt-4 overflow-x-auto p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Permission</th>
                    @foreach($roles as $role)
                        <th scope="col" class="text-center">{{ $role->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($permissions as $permission)
                    <tr wire:key="permission-{{ $permission->id }}">
                        <td class="font-medium text-ink-primary">{{ $permission->name }}</td>
                        @foreach($roles as $role)
                            @php($dimiliki = $role->permissions->contains('id', $permission->id))
                            <td class="text-center">
                                @if($bisaKelola && $role->name !== 'sysadmin')
                                    <button
                                        type="button"
                                        wire:click="togglePermission({{ $role->id }}, {{ $permission->id }})"
                                        wire:key="toggle-{{ $role->id }}-{{ $permission->id }}"
                                        class="inline-flex h-6 w-6 items-center justify-center rounded hover:bg-surface-subtle"
                                        title="{{ $dimiliki ? 'Cabut dari' : 'Berikan ke' }} {{ $role->name }}"
                                    >
                                        @if($dimiliki)
                                            <x-icon name="check" size="16" class="text-success-solid" />
                                        @else
                                            <span class="text-ink-disabled">—</span>
                                        @endif
                                    </button>
                                @else
                                    @if($dimiliki)
                                        <x-icon name="check" size="16" class="inline text-success-solid" />
                                    @else
                                        <span class="text-ink-disabled">—</span>
                                    @endif
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ $roles->count() + 1 }}"><x-empty-state title="Belum ada permission terdaftar" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
