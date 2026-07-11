<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RepairRequestSeeder extends Seeder
{
    use WithoutModelEvents;

    private const CATATAN = [
        'Suara bising tidak normal saat beroperasi',
        'Terdapat kebocoran oli pada bagian bearing',
        'Getaran berlebih saat mesin berjalan',
        'Komponen aus dan perlu penggantian',
        'Suhu mesin melebihi batas normal',
        'Terjadi kebocoran uap pada sambungan pipa',
        'Motor penggerak tidak berfungsi optimal',
        'Terdapat karat pada bagian struktur',
        'Tekanan tidak stabil saat operasi',
        'Kabel kelistrikan terkelupas',
    ];

    public function run(): void
    {
        $assets = Asset::orderBy('id')->limit(50)->get();
        $mekanikUsers = User::where('role', 'mekanik')->limit(20)->get();
        $teknik = User::where('role', 'teknik')->first();

        if ($assets->isEmpty() || $mekanikUsers->isEmpty()) {
            return;
        }

        $kondisiOptions = ['ringan', 'sedang', 'berat'];
        $statusOptions = ['pending', 'approved', 'rejected'];

        for ($i = 1; $i <= 35; $i++) {
            $asset = $assets[($i - 1) % $assets->count()];
            $mekanik = $mekanikUsers[($i - 1) % $mekanikUsers->count()];
            $status = $statusOptions[$i % count($statusOptions)];
            $isVerified = $status !== 'pending';

            RepairRequest::create([
                'asset_id' => $asset->id,
                'mechanic_id' => $mekanik->id,
                'kondisi_perbaikan' => $kondisiOptions[$i % count($kondisiOptions)],
                'catatan_kerusakan' => self::CATATAN[$i % count(self::CATATAN)],
                'bukti_foto' => null,
                'status_verifikasi' => $status,
                'verified_by' => $isVerified ? $teknik?->id : null,
                'verified_at' => $isVerified ? now()->subDays(rand(0, 10)) : null,
                'catatan_verifikasi' => $status === 'rejected'
                    ? 'Data kurang lengkap, mohon lampirkan bukti foto ulang.'
                    : ($status === 'approved' ? 'Disetujui untuk ditindaklanjuti.' : null),
            ]);
        }
    }
}
