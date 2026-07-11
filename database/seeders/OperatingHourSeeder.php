<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\OperatingHour;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OperatingHourSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Isi jam jalan untuk 100 aset pertama, satu record per aset.
     */
    public function run(): void
    {
        $mekanik = User::where('role', 'mekanik')->first();

        Asset::orderBy('id')
            ->limit(100)
            ->get()
            ->each(function (Asset $asset) use ($mekanik) {
                OperatingHour::firstOrCreate(
                    [
                        'asset_id' => $asset->id,
                        'tanggal' => now()->toDateString(),
                    ],
                    [
                        'user_id' => $mekanik?->id,
                        'jam_jalan' => rand(50, 900),
                        'keterangan' => 'Seed data jam jalan',
                    ]
                );
            });
    }
}
