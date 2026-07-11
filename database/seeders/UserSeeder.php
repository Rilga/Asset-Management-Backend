<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Role Teknik (Supervisor/Admin Aset)
        User::firstOrCreate(
            ['username' => 'teknik_01'],
            [
                'name' => 'Budi Teknik',
                'email' => 'teknik01@pabrik.com',
                'no_telp' => '081233334444',
                'role' => 'teknik',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Akun Role Mekanik (Pelaksana Lapangan)
        User::firstOrCreate(
            ['username' => 'mekanik_01'],
            [
                'name' => 'Andi Mekanik',
                'email' => 'mekanik01@pabrik.com',
                'no_telp' => '081299998888',
                'role' => 'mekanik',
                'password' => Hash::make('password123'),
            ]
        );

        // 3. 100 akun Mekanik tambahan (mekanik_02 .. mekanik_100)
        $mekanikPassword = Hash::make('password123');

        for ($i = 2; $i <= 100; $i++) {
            $number = str_pad($i, 2, '0', STR_PAD_LEFT);

            User::firstOrCreate(
                ['username' => "mekanik_{$number}"],
                [
                    'name' => "Mekanik {$number}",
                    'email' => "mekanik{$number}@pabrik.com",
                    'no_telp' => '0812' . str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT),
                    'role' => 'mekanik',
                    'password' => $mekanikPassword,
                ]
            );
        }
    }
}
