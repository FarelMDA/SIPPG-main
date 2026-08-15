<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DaerahSeeder::class,
            DesaSeeder::class,
            KelompokSeeder::class,
            RolePermissionSeeder::class,
        ]);

        if (! User::where('username', 'admin')->exists()) {
            $admin = User::create([
                'nama' => 'Admin Daerah',
                'username' => 'admin',
                'password' => 'password', // must_change_password memaksa ganti di login pertama
                'must_change_password' => true,
                'is_active' => true,
            ]);

            $admin->assignRole('admin-daerah');
        }
    }
}
