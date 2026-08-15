<?php

namespace Database\Factories;

use App\Models\Jenjang;
use App\Models\Kelas;
use App\Models\Kelompok;
use Illuminate\Database\Eloquent\Factories\Factory;

class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    /**
     * Round-robin, bukan random murni — kelas.(kelompok_id,jenjang_id) unik, jadi dua
     * factory create() berturut-turut untuk Kelompok yang sama tidak boleh tabrakan.
     */
    private static int $jenjangCursor = 0;

    public function definition(): array
    {
        return [
            'kelompok_id' => Kelompok::factory(),
            'jenjang_id' => function () {
                $jenjangIds = Jenjang::orderBy('urutan')->pluck('id');

                if ($jenjangIds->isEmpty()) {
                    return null;
                }

                return $jenjangIds[self::$jenjangCursor++ % $jenjangIds->count()];
            },
            'status_aktif' => true,
        ];
    }
}
