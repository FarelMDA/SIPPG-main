<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Provisioning akun `sysadmin` (level tertinggi, satu-satunya pemegang `roles.manage`)
 * SENGAJA hanya lewat console — bukan lewat Admin\KelolaPengguna (UI web) — supaya
 * tidak ada akun web manapun (termasuk admin-daerah) yang bisa membuat/menaikkan akun
 * ke level ini sendiri (privilege escalation). Lihat RolePermissionSeeder.php.
 */
class BuatSysadmin extends Command
{
    protected $signature = 'sysadmin:buat {nama} {username}';

    protected $description = 'Buat akun sysadmin baru (level tertinggi, hanya lewat console — lihat RolePermissionSeeder.php)';

    public function handle(): int
    {
        $nama = $this->argument('nama');
        $username = $this->argument('username');

        try {
            $this->laravel['validator']->make(
                ['username' => $username],
                ['username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')]],
            )->validate();
        } catch (ValidationException $e) {
            $this->error($e->validator->errors()->first());

            return self::FAILURE;
        }

        $password = Str::password(12, symbols: false);

        $user = User::create([
            'nama' => $nama,
            'username' => $username,
            'password' => Hash::make($password),
            'kelompok_id' => null,
            'desa_id' => null,
            'must_change_password' => true,
            'is_active' => true,
        ]);

        $user->assignRole('sysadmin');

        $this->info("Akun sysadmin '{$username}' berhasil dibuat.");
        $this->warn("Password awal: {$password}");
        $this->line('Sampaikan password ini secara langsung — tidak disimpan/ditampilkan ulang oleh sistem.');

        return self::SUCCESS;
    }
}
