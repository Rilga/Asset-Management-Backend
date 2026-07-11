<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $teknik = User::where('role', 'teknik')->first();

        $assets = [
            // Stasiun Sterilisasi (8)
            ['kategori' => 'Pressure Vessel', 'nomor_peralatan' => 'STR-001', 'nama_mesin' => 'Sterilizer I',                    'area_mesin' => 'Stasiun Sterilisasi', 'merek' => 'Stork',           'tahun_pembelian' => 2018, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pressure Vessel', 'nomor_peralatan' => 'STR-002', 'nama_mesin' => 'Sterilizer II',                   'area_mesin' => 'Stasiun Sterilisasi', 'merek' => 'Stork',           'tahun_pembelian' => 2018, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pressure Vessel', 'nomor_peralatan' => 'STR-003', 'nama_mesin' => 'Sterilizer III',                  'area_mesin' => 'Stasiun Sterilisasi', 'merek' => 'Sangkuriang',     'tahun_pembelian' => 2020, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pressure Vessel', 'nomor_peralatan' => 'STR-004', 'nama_mesin' => 'Sterilizer IV',                   'area_mesin' => 'Stasiun Sterilisasi', 'merek' => 'Sangkuriang',     'tahun_pembelian' => 2021, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'STR-005', 'nama_mesin' => 'Pompa Kondensat Sterilizer',      'area_mesin' => 'Stasiun Sterilisasi', 'merek' => 'Grundfos',        'tahun_pembelian' => 2019, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'STR-006', 'nama_mesin' => 'Transfer Car I',                  'area_mesin' => 'Stasiun Sterilisasi', 'merek' => 'Liaohe',          'tahun_pembelian' => 2018, 'maintenance_interval_hours' => 300],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'STR-007', 'nama_mesin' => 'Transfer Car II',                 'area_mesin' => 'Stasiun Sterilisasi', 'merek' => 'Liaohe',          'tahun_pembelian' => 2018, 'maintenance_interval_hours' => 300],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'STR-008', 'nama_mesin' => 'Rail Sterilizer',                 'area_mesin' => 'Stasiun Sterilisasi', 'merek' => 'Lokal',           'tahun_pembelian' => 2018, 'maintenance_interval_hours' => 1000],

            // Stasiun Penebahan (6)
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'THR-001', 'nama_mesin' => 'Hoisting Crane I',                'area_mesin' => 'Stasiun Penebahan',   'merek' => 'Demag',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'THR-002', 'nama_mesin' => 'Hoisting Crane II',               'area_mesin' => 'Stasiun Penebahan',   'merek' => 'Demag',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'THR-003', 'nama_mesin' => 'Thresher I',                      'area_mesin' => 'Stasiun Penebahan',   'merek' => 'Hanshen',         'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 300],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'THR-004', 'nama_mesin' => 'Thresher II',                     'area_mesin' => 'Stasiun Penebahan',   'merek' => 'Hanshen',         'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 300],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'THR-005', 'nama_mesin' => 'Fruit Conveyor I',                'area_mesin' => 'Stasiun Penebahan',   'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 300],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'THR-006', 'nama_mesin' => 'Empty Bunch Conveyor',            'area_mesin' => 'Stasiun Penebahan',   'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 300],

            // Stasiun Kempa (8)
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'PRS-001', 'nama_mesin' => 'Digester I',                      'area_mesin' => 'Stasiun Kempa',       'merek' => 'Usine Sangkuriang', 'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 200],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'PRS-002', 'nama_mesin' => 'Digester II',                     'area_mesin' => 'Stasiun Kempa',       'merek' => 'Usine Sangkuriang', 'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 200],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'PRS-003', 'nama_mesin' => 'Digester III',                    'area_mesin' => 'Stasiun Kempa',       'merek' => 'Usine Sangkuriang', 'tahun_pembelian' => 2020, 'maintenance_interval_hours' => 200],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'SCR-001', 'nama_mesin' => 'Screw Press I',                   'area_mesin' => 'Stasiun Kempa',       'merek' => 'French Oil',      'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 200],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'SCR-002', 'nama_mesin' => 'Screw Press II',                  'area_mesin' => 'Stasiun Kempa',       'merek' => 'French Oil',      'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 200],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'SCR-003', 'nama_mesin' => 'Screw Press III',                 'area_mesin' => 'Stasiun Kempa',       'merek' => 'French Oil',      'tahun_pembelian' => 2020, 'maintenance_interval_hours' => 200],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'PRS-004', 'nama_mesin' => 'Cake Breaker Conveyor I',         'area_mesin' => 'Stasiun Kempa',       'merek' => 'Lokal',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 300],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'PRS-005', 'nama_mesin' => 'Cake Breaker Conveyor II',        'area_mesin' => 'Stasiun Kempa',       'merek' => 'Lokal',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 300],

            // Stasiun Klarifikasi (12)
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'CLR-001', 'nama_mesin' => 'Vibrating Screen I',              'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Lokal',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'CLR-002', 'nama_mesin' => 'Vibrating Screen II',             'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Lokal',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'CLR-003', 'nama_mesin' => 'CST I (Continuous Settling Tank)', 'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Lokal',          'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 2000],
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'CLR-004', 'nama_mesin' => 'CST II (Continuous Settling Tank)', 'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Lokal',         'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 2000],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'CLR-005', 'nama_mesin' => 'Sludge Pit Pump I',               'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Ebara',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'CLR-006', 'nama_mesin' => 'Sludge Pit Pump II',              'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Ebara',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'CTF-001', 'nama_mesin' => 'Centrifuge (Decanter) I',         'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Alfa Laval',      'tahun_pembelian' => 2019, 'maintenance_interval_hours' => 400],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'CTF-002', 'nama_mesin' => 'Centrifuge (Decanter) II',        'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'GEA Westfalia',   'tahun_pembelian' => 2019, 'maintenance_interval_hours' => 400],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'CLR-007', 'nama_mesin' => 'Vacuum Dryer I',                  'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Canzler',         'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'CLR-008', 'nama_mesin' => 'Vacuum Dryer II',                 'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Canzler',         'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'CLR-009', 'nama_mesin' => 'Oil Purifier I',                  'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Alfa Laval',      'tahun_pembelian' => 2018, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'CLR-010', 'nama_mesin' => 'CPO Transfer Pump I',             'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Grundfos',        'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],

            // Stasiun Kernel (10)
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'KRN-001', 'nama_mesin' => 'Depericarper I',                  'area_mesin' => 'Stasiun Kernel',      'merek' => 'Lokal',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 300],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'KRN-002', 'nama_mesin' => 'Nut Polishing Drum I',            'area_mesin' => 'Stasiun Kernel',      'merek' => 'Lokal',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 300],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'KRN-003', 'nama_mesin' => 'Nut Polishing Drum II',           'area_mesin' => 'Stasiun Kernel',      'merek' => 'Lokal',           'tahun_pembelian' => 2020, 'maintenance_interval_hours' => 300],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'KRN-004', 'nama_mesin' => 'Nut Cracker I',                   'area_mesin' => 'Stasiun Kernel',      'merek' => 'Lokal',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 200],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'KRN-005', 'nama_mesin' => 'Nut Cracker II',                  'area_mesin' => 'Stasiun Kernel',      'merek' => 'Lokal',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 200],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'KRN-006', 'nama_mesin' => 'LTDS I (Light Tenera Dry Separator)', 'area_mesin' => 'Stasiun Kernel', 'merek' => 'Lokal',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 300],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'KRN-007', 'nama_mesin' => 'LTDS II (Light Tenera Dry Separator)', 'area_mesin' => 'Stasiun Kernel', 'merek' => 'Lokal',          'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 300],
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'KRN-008', 'nama_mesin' => 'Kernel Silo I',                   'area_mesin' => 'Stasiun Kernel',      'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 1000],
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'KRN-009', 'nama_mesin' => 'Kernel Silo II',                  'area_mesin' => 'Stasiun Kernel',      'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 1000],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'KRN-010', 'nama_mesin' => 'Kernel Transport Fan',            'area_mesin' => 'Stasiun Kernel',      'merek' => 'Lokal',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],

            // Stasiun Ketel Uap (10)
            ['kategori' => 'Pressure Vessel', 'nomor_peralatan' => 'BLR-001', 'nama_mesin' => 'Boiler I (Ketel Uap)',            'area_mesin' => 'Stasiun Ketel Uap',   'merek' => 'Takuma',          'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 1000],
            ['kategori' => 'Pressure Vessel', 'nomor_peralatan' => 'BLR-002', 'nama_mesin' => 'Boiler II (Ketel Uap)',           'area_mesin' => 'Stasiun Ketel Uap',   'merek' => 'Takuma',          'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 1000],
            ['kategori' => 'Pressure Vessel', 'nomor_peralatan' => 'BLR-003', 'nama_mesin' => 'Boiler III (Ketel Uap)',          'area_mesin' => 'Stasiun Ketel Uap',   'merek' => 'Thermax',         'tahun_pembelian' => 2022, 'maintenance_interval_hours' => 1000],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'BLR-004', 'nama_mesin' => 'BFD Pump I',                      'area_mesin' => 'Stasiun Ketel Uap',   'merek' => 'KSB',             'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'BLR-005', 'nama_mesin' => 'BFD Pump II',                     'area_mesin' => 'Stasiun Ketel Uap',   'merek' => 'KSB',             'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'BLR-006', 'nama_mesin' => 'Feed Water Pump I',               'area_mesin' => 'Stasiun Ketel Uap',   'merek' => 'Grundfos',        'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'BLR-007', 'nama_mesin' => 'Feed Water Pump II',              'area_mesin' => 'Stasiun Ketel Uap',   'merek' => 'Grundfos',        'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pressure Vessel', 'nomor_peralatan' => 'BLR-008', 'nama_mesin' => 'Deaerator',                       'area_mesin' => 'Stasiun Ketel Uap',   'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 2000],
            ['kategori' => 'Pressure Vessel', 'nomor_peralatan' => 'BLR-009', 'nama_mesin' => 'Blow Down Tank',                  'area_mesin' => 'Stasiun Ketel Uap',   'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 2000],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'BLR-010', 'nama_mesin' => 'Forced Draft Fan Boiler I',       'area_mesin' => 'Stasiun Ketel Uap',   'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],

            // Stasiun Pembangkit (8)
            ['kategori' => 'Turbin',          'nomor_peralatan' => 'GNS-001', 'nama_mesin' => 'Steam Turbine I',                 'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'Shin Nippon',     'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 2000],
            ['kategori' => 'Turbin',          'nomor_peralatan' => 'GNS-002', 'nama_mesin' => 'Steam Turbine II',                'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'Siemens',         'tahun_pembelian' => 2020, 'maintenance_interval_hours' => 2000],
            ['kategori' => 'Genset',          'nomor_peralatan' => 'GNS-003', 'nama_mesin' => 'Generator I',                     'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'Leroy Somer',     'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 1000],
            ['kategori' => 'Genset',          'nomor_peralatan' => 'GNS-004', 'nama_mesin' => 'Generator II',                    'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'Stamford',        'tahun_pembelian' => 2020, 'maintenance_interval_hours' => 1000],
            ['kategori' => 'Pressure Vessel', 'nomor_peralatan' => 'GNS-005', 'nama_mesin' => 'Back Pressure Vessel',            'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 2000],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'GNS-006', 'nama_mesin' => 'Condenser I',                     'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'Alfa Laval',      'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 1000],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'GNS-007', 'nama_mesin' => 'Cooling Tower I',                 'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Genset',          'nomor_peralatan' => 'GNS-008', 'nama_mesin' => 'Diesel Generator (Backup)',       'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'Cummins',         'tahun_pembelian' => 2019, 'maintenance_interval_hours' => 250],

            // Kompresor (4)
            ['kategori' => 'Kompresor',       'nomor_peralatan' => 'CMP-001', 'nama_mesin' => 'Kompresor Udara I',               'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'Atlas Copco',     'tahun_pembelian' => 2021, 'maintenance_interval_hours' => 250],
            ['kategori' => 'Kompresor',       'nomor_peralatan' => 'CMP-002', 'nama_mesin' => 'Kompresor Udara II',              'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'Atlas Copco',     'tahun_pembelian' => 2021, 'maintenance_interval_hours' => 250],
            ['kategori' => 'Kompresor',       'nomor_peralatan' => 'CMP-003', 'nama_mesin' => 'Kompresor Udara III',             'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'Ingersoll Rand',  'tahun_pembelian' => 2018, 'maintenance_interval_hours' => 250],
            ['kategori' => 'Kompresor',       'nomor_peralatan' => 'CMP-004', 'nama_mesin' => 'Kompresor Instrumentasi',         'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'Kaeser',          'tahun_pembelian' => 2022, 'maintenance_interval_hours' => 500],

            // Utilitas & Pengolahan Air (8)
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'UTL-001', 'nama_mesin' => 'Water Treatment Plant',           'area_mesin' => 'Utilitas',            'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 1000],
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'UTL-002', 'nama_mesin' => 'Clarifier Tank',                  'area_mesin' => 'Utilitas',            'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 2000],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'UTL-003', 'nama_mesin' => 'Sand Filter I',                   'area_mesin' => 'Utilitas',            'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'UTL-004', 'nama_mesin' => 'Sand Filter II',                  'area_mesin' => 'Utilitas',            'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'UTL-005', 'nama_mesin' => 'Water Softener I',                'area_mesin' => 'Utilitas',            'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 1000],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'UTL-006', 'nama_mesin' => 'Chemical Dosing Pump',            'area_mesin' => 'Utilitas',            'merek' => 'Grundfos',        'tahun_pembelian' => 2018, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'UTL-007', 'nama_mesin' => 'Raw Water Pump I',                'area_mesin' => 'Utilitas',            'merek' => 'Ebara',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'UTL-008', 'nama_mesin' => 'Raw Water Pump II',               'area_mesin' => 'Utilitas',            'merek' => 'Ebara',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],

            // Pompa Proses (10)
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'PMP-001', 'nama_mesin' => 'CPO Transfer Pump II',            'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Grundfos',        'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'PMP-002', 'nama_mesin' => 'Hot Water Pump I',                'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Grundfos',        'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'PMP-003', 'nama_mesin' => 'Hot Water Pump II',               'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Grundfos',        'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'PMP-004', 'nama_mesin' => 'Cooling Water Pump I',            'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'KSB',             'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'PMP-005', 'nama_mesin' => 'Cooling Water Pump II',           'area_mesin' => 'Stasiun Pembangkit',  'merek' => 'KSB',             'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'PMP-006', 'nama_mesin' => 'Fire Pump',                       'area_mesin' => 'Utilitas',            'merek' => 'Flowserve',       'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 1000],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'PMP-007', 'nama_mesin' => 'Condensate Pump I',               'area_mesin' => 'Stasiun Ketel Uap',   'merek' => 'Grundfos',        'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'PMP-008', 'nama_mesin' => 'Condensate Pump II',              'area_mesin' => 'Stasiun Ketel Uap',   'merek' => 'Grundfos',        'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'PMP-009', 'nama_mesin' => 'Sludge Transfer Pump I',          'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Ebara',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 300],
            ['kategori' => 'Pompa',           'nomor_peralatan' => 'PMP-010', 'nama_mesin' => 'Sludge Transfer Pump II',         'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Ebara',           'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 300],

            // Tangki Penyimpanan (8)
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'TNK-001', 'nama_mesin' => 'Storage Tank CPO I (1000T)',      'area_mesin' => 'Area Tangki',         'merek' => 'Lokal',           'tahun_pembelian' => 2015, 'maintenance_interval_hours' => 4000],
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'TNK-002', 'nama_mesin' => 'Storage Tank CPO II (1000T)',     'area_mesin' => 'Area Tangki',         'merek' => 'Lokal',           'tahun_pembelian' => 2015, 'maintenance_interval_hours' => 4000],
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'TNK-003', 'nama_mesin' => 'Storage Tank CPO III (500T)',     'area_mesin' => 'Area Tangki',         'merek' => 'Lokal',           'tahun_pembelian' => 2021, 'maintenance_interval_hours' => 4000],
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'TNK-004', 'nama_mesin' => 'Sludge Tank',                    'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 2000],
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'TNK-005', 'nama_mesin' => 'Oil Recovery Tank',              'area_mesin' => 'Stasiun Klarifikasi', 'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 2000],
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'TNK-006', 'nama_mesin' => 'Hot Water Tank',                 'area_mesin' => 'Utilitas',            'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 2000],
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'TNK-007', 'nama_mesin' => 'Fuel Oil Tank',                  'area_mesin' => 'Utilitas',            'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 4000],
            ['kategori' => 'Tangki',          'nomor_peralatan' => 'TNK-008', 'nama_mesin' => 'Treated Water Tank',             'area_mesin' => 'Utilitas',            'merek' => 'Lokal',           'tahun_pembelian' => 2016, 'maintenance_interval_hours' => 4000],

            // Workshop & Peralatan Umum (10)
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'WRK-001', 'nama_mesin' => 'Overhead Crane Workshop',        'area_mesin' => 'Workshop',            'merek' => 'Konecranes',      'tahun_pembelian' => 2017, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'WRK-002', 'nama_mesin' => 'Welding Machine I (MIG)',        'area_mesin' => 'Workshop',            'merek' => 'Lincoln Electric', 'tahun_pembelian' => 2018, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'WRK-003', 'nama_mesin' => 'Welding Machine II (SMAW)',      'area_mesin' => 'Workshop',            'merek' => 'Miller',          'tahun_pembelian' => 2018, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'WRK-004', 'nama_mesin' => 'Lathe Machine',                  'area_mesin' => 'Workshop',            'merek' => 'KNUTH',           'tahun_pembelian' => 2019, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'WRK-005', 'nama_mesin' => 'Drill Press',                    'area_mesin' => 'Workshop',            'merek' => 'Lokal',           'tahun_pembelian' => 2018, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'WRK-006', 'nama_mesin' => 'Surface Grinder',                'area_mesin' => 'Workshop',            'merek' => 'Makita',          'tahun_pembelian' => 2020, 'maintenance_interval_hours' => 200],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'WRK-007', 'nama_mesin' => 'Forklift I (3 Ton)',             'area_mesin' => 'Workshop',            'merek' => 'Toyota',          'tahun_pembelian' => 2019, 'maintenance_interval_hours' => 250],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'WRK-008', 'nama_mesin' => 'Forklift II (5 Ton)',            'area_mesin' => 'Workshop',            'merek' => 'Komatsu',         'tahun_pembelian' => 2021, 'maintenance_interval_hours' => 250],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'WRK-009', 'nama_mesin' => 'Mobile Crane (25 Ton)',          'area_mesin' => 'Workshop',            'merek' => 'Tadano',          'tahun_pembelian' => 2018, 'maintenance_interval_hours' => 500],
            ['kategori' => 'Mesin Produksi',  'nomor_peralatan' => 'WRK-010', 'nama_mesin' => 'Hydraulic Press',                'area_mesin' => 'Workshop',            'merek' => 'Enerpac',         'tahun_pembelian' => 2020, 'maintenance_interval_hours' => 500],
        ];

        foreach ($assets as $data) {
            Asset::create(array_merge($data, ['created_by' => $teknik?->id]));
        }
    }
}
