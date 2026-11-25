<?php

namespace Database\Seeders;

use App\Models\DtcLibrary;
use App\Models\MaintenanceRecord;
use App\Models\TelematicsDevice;
use App\Models\TelemetryLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleFaultLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with MOTEC initial data.
     */
    public function run(): void
    {
        // 1. User Utama
        $user = User::create([
            'full_name' => 'Rian Adiputra',
            'name' => 'Rian Adiputra',
            'email' => 'rian.adi@motec.id',
            'password_hash' => 'pbkdf2_sha256$hashdemo',
            'password' => Hash::make('secret123'),
            'phone' => '+6281234567890',
            'role' => 'owner',
        ]);

        // 2. Master Kamus Kode Gangguan Standar Motor (DTC Library)
        $dtcs = [
            [
                'code' => 'P0115',
                'category' => 'Cooling / Sensor',
                'title_id' => 'Gangguan Sirkuit Sensor Suhu Mesin / ECT',
                'title_en' => 'Engine Coolant Temperature Circuit Malfunction',
                'severity' => 'critical',
                'description_id' => 'ECU membaca nilai voltase sensor ECT di luar rentang normal. Kipas radiator mungkin berputar terus menerus atau indikator suhu berkedip merah.',
                'recommendation_id' => 'Periksa soket kabel sensor ECT pada blok silinder mesin, bersihkan dari kerak air, atau ganti unit sensor temperatur baru.',
            ],
            [
                'code' => 'P0120',
                'category' => 'Powertrain / TPS',
                'title_id' => 'Gangguan Sirkuit Sensor Posisi Gas / TPS',
                'title_en' => 'Throttle Position Sensor A Circuit Malfunction',
                'severity' => 'warning',
                'description_id' => 'Respon bukaan gas tidak terbaca halus oleh ECU, berpotensi menyebabkan motor tersendat-sendat saat ditarik gas tiba-tiba.',
                'recommendation_id' => 'Lakukan pembersihan throttle body dan reset nilai bukaan gas minimum di bengkel resmi atau gunakan pembersih kontak elektrik pada soket TPS.',
            ],
            [
                'code' => 'P0562',
                'category' => 'Electrical / Baterai',
                'title_id' => 'Tegangan Sistem Kelistrikan Rendah (Under Voltage)',
                'title_en' => 'System Voltage Low',
                'severity' => 'critical',
                'description_id' => 'Tegangan aki turun di bawah 11.5 Volt saat sistem aktif. Berisiko sistem injeksi dan pompa bensin tidak mendapatkan daya yang cukup.',
                'recommendation_id' => 'Cek kondisi aki, lakukan charge eksternal. Jika tegangan tidak naik saat mesin hidup di atas 3000 RPM, periksa spul dan kiprok.',
            ],
            [
                'code' => 'P0130',
                'category' => 'Emisi / O2 Sensor',
                'title_id' => 'Gangguan Sirkuit Sensor Oksigen (O2 Lambda)',
                'title_en' => 'O2 Sensor Circuit Malfunction (Bank 1)',
                'severity' => 'warning',
                'description_id' => 'Sensor O2 di leher knalpot tidak merespon fluktuasi gas buang. Motor mungkin terasa lebih boros bensin dan knalpot sedikit berbau bensin mentah.',
                'recommendation_id' => 'Bongkar sensor O2, bersihkan jelaga hitam dengan semprotan khusus, dan pastikan kabel tidak terjepit atau meleleh terkena panas knalpot.',
            ],
            [
                'code' => 'P0335',
                'category' => 'Pengapian / Crankshaft',
                'title_id' => 'Sirkuit Sensor Putaran Kruk As (CKP) Bermasalah',
                'title_en' => 'Crankshaft Position Sensor A Circuit',
                'severity' => 'critical',
                'description_id' => 'Sinyal putaran mesin terputus-putus. Mesin bisa mati mendadak saat berjalan atau sulit dihidupkan.',
                'recommendation_id' => 'Cek kabel sensor CKP di dekat bak magnet, pastikan jarak pick-up rotor magnet bersih dari serpihan gram besi.',
            ],
            [
                'code' => 'P0505',
                'category' => 'Air Intake / Idle',
                'title_id' => 'Gangguan Sistem Kontrol Putaran Langsam (IACV)',
                'title_en' => 'Idle Air Control System Malfunction',
                'severity' => 'info',
                'description_id' => 'Solenoid pengatur udara langsam kotor sehingga putaran idle tidak stabil saat mesin dingin maupun panas.',
                'recommendation_id' => 'Semprot lubang bypass langsam pada throttle body dengan karburator/throttle body cleaner.',
            ],
        ];

        foreach ($dtcs as $d) {
            DtcLibrary::create($d);
        }

        // 3. Sepeda Motor Sampel (4 Kendaraan dengan ragam status kesehatan)
        $now = now();

        // 3.1 Yamaha NMAX 155 (Kondisi Prima / Skor Tinggi)
        $nmax = Vehicle::create([
            'user_id' => $user->id,
            'plate_number' => 'B 4921 TXP',
            'brand' => 'Yamaha',
            'model' => 'NMAX 155 Connected VVA',
            'year' => 2024,
            'vin_chassis' => 'MH3SG5210PK129841',
            'engine_number' => 'G3J8E091823',
            'engine_displacement_cc' => 155,
            'cooling_type' => 'liquid',
            'fuel_capacity_liter' => 7.1,
            'current_odometer_km' => 8450.0,
            'telematics_unit_id' => 'MOTEC-NMAX-ESP32',
            'is_connected' => true,
            'last_ping_at' => $now,
        ]);

        TelematicsDevice::create([
            'vehicle_id' => $nmax->id,
            'device_uid' => 'MOTEC-NMAX-ESP32',
            'device_model' => 'MOTEC-CAN-ESP32-V2',
            'firmware_version' => 'v2.4.1',
            'protocol' => 'CAN-BUS/MQTT',
            'api_key' => 'key_nmax_esp32',
            'status' => 'active',
        ]);

        TelemetryLog::create([
            'vehicle_id' => $nmax->id,
            'timestamp' => $now,
            'engine_rpm' => 1650,
            'speed_kmh' => 0.0,
            'engine_temp_c' => 84.5,
            'tps_throttle_percent' => 0.0,
            'gear_position' => 0,
            'battery_voltage_v' => 14.2,
            'stator_charging_amp' => 3.8,
            'oil_health_percent' => 92.0,
            'oil_pressure_kpa' => 260.0,
            'o2_sensor_v' => 0.48,
            'map_sensor_kpa' => 101.2,
            'front_tire_psi' => 29.5,
            'rear_tire_psi' => 34.0,
            'brake_pad_front_percent' => 88.0,
            'brake_pad_rear_percent' => 85.0,
            'fuel_level_percent' => 78.0,
            'ambient_temp_c' => 29.5,
            'sidestand_down' => false,
            'lean_angle_deg' => 0.0,
            'odometer_km' => 8450.0,
        ]);

        // 3.2 Honda PCX 160 (Aki Drop / Under Voltage)
        $pcx = Vehicle::create([
            'user_id' => $user->id,
            'plate_number' => 'B 3812 KDA',
            'brand' => 'Honda',
            'model' => 'PCX 160 e:HEV Smart Key',
            'year' => 2023,
            'vin_chassis' => 'MH1KF5113NK772109',
            'engine_number' => 'KF51E103849',
            'engine_displacement_cc' => 160,
            'cooling_type' => 'liquid',
            'fuel_capacity_liter' => 8.1,
            'current_odometer_km' => 19200.0,
            'telematics_unit_id' => 'MOTEC-PCX-ESP32',
            'is_connected' => true,
            'last_ping_at' => $now,
        ]);

        TelematicsDevice::create([
            'vehicle_id' => $pcx->id,
            'device_uid' => 'MOTEC-PCX-ESP32',
            'device_model' => 'MOTEC-CAN-ESP32-V2',
            'firmware_version' => 'v2.4.1',
            'protocol' => 'CAN-BUS/MQTT',
            'api_key' => 'key_pcx_esp32',
            'status' => 'active',
        ]);

        TelemetryLog::create([
            'vehicle_id' => $pcx->id,
            'timestamp' => $now,
            'engine_rpm' => 0,
            'speed_kmh' => 0.0,
            'engine_temp_c' => 48.0,
            'tps_throttle_percent' => 0.0,
            'gear_position' => 0,
            'battery_voltage_v' => 11.4, // Drop
            'stator_charging_amp' => 0.0,
            'oil_health_percent' => 68.0,
            'oil_pressure_kpa' => 0.0,
            'o2_sensor_v' => 0.45,
            'map_sensor_kpa' => 101.3,
            'front_tire_psi' => 28.0,
            'rear_tire_psi' => 31.5,
            'brake_pad_front_percent' => 70.0,
            'brake_pad_rear_percent' => 65.0,
            'fuel_level_percent' => 42.0,
            'ambient_temp_c' => 31.0,
            'sidestand_down' => true,
            'lean_angle_deg' => -12.0,
            'odometer_km' => 19200.0,
        ]);

        VehicleFaultLog::create([
            'vehicle_id' => $pcx->id,
            'dtc_code' => 'P0562',
            'is_active' => true,
            'detected_at' => $now,
        ]);

        // 3.3 Kawasaki Ninja ZX-25R (Overheat + DTC P0115)
        $zx = Vehicle::create([
            'user_id' => $user->id,
            'plate_number' => 'B 6620 SRO',
            'brand' => 'Kawasaki',
            'model' => 'Ninja ZX-25R SE (4-Silinder)',
            'year' => 2023,
            'vin_chassis' => 'JKAZX250PPA092155',
            'engine_number' => 'ZX250EE01428',
            'engine_displacement_cc' => 250,
            'cooling_type' => 'liquid',
            'fuel_capacity_liter' => 15.0,
            'current_odometer_km' => 6200.0,
            'telematics_unit_id' => 'MOTEC-ZX25R-ESP32',
            'is_connected' => true,
            'last_ping_at' => $now,
        ]);

        TelematicsDevice::create([
            'vehicle_id' => $zx->id,
            'device_uid' => 'MOTEC-ZX25R-ESP32',
            'device_model' => 'MOTEC-CAN-ESP32-V2',
            'firmware_version' => 'v2.4.1',
            'protocol' => 'CAN-BUS/MQTT',
            'api_key' => 'key_zx25r_esp32',
            'status' => 'active',
        ]);

        TelemetryLog::create([
            'vehicle_id' => $zx->id,
            'timestamp' => $now,
            'engine_rpm' => 3500,
            'speed_kmh' => 38.0,
            'engine_temp_c' => 109.8, // Overheat
            'tps_throttle_percent' => 12.0,
            'gear_position' => 2,
            'battery_voltage_v' => 13.9,
            'stator_charging_amp' => 4.2,
            'oil_health_percent' => 54.0,
            'oil_pressure_kpa' => 290.0,
            'o2_sensor_v' => 0.62,
            'map_sensor_kpa' => 98.5,
            'front_tire_psi' => 31.0,
            'rear_tire_psi' => 35.0,
            'brake_pad_front_percent' => 52.0,
            'brake_pad_rear_percent' => 48.0,
            'fuel_level_percent' => 85.0,
            'ambient_temp_c' => 33.0,
            'sidestand_down' => false,
            'lean_angle_deg' => 8.5,
            'odometer_km' => 6200.0,
        ]);

        VehicleFaultLog::create([
            'vehicle_id' => $zx->id,
            'dtc_code' => 'P0115',
            'is_active' => true,
            'detected_at' => $now,
        ]);

        // 3.4 Honda Vario 160 (Oli Rendah & Ban Kempes)
        $vario = Vehicle::create([
            'user_id' => $user->id,
            'plate_number' => 'B 5119 JKT',
            'brand' => 'Honda',
            'model' => 'Vario 160 CBS',
            'year' => 2022,
            'vin_chassis' => 'MH1KD1119MK449120',
            'engine_number' => 'KD11E201948',
            'engine_displacement_cc' => 160,
            'cooling_type' => 'liquid',
            'fuel_capacity_liter' => 5.5,
            'current_odometer_km' => 27800.0,
            'telematics_unit_id' => 'MOTEC-VARIO-ESP32',
            'is_connected' => false,
            'last_ping_at' => $now->copy()->subHours(2),
        ]);

        TelematicsDevice::create([
            'vehicle_id' => $vario->id,
            'device_uid' => 'MOTEC-VARIO-ESP32',
            'device_model' => 'MOTEC-CAN-ESP32-V2',
            'firmware_version' => 'v2.4.1',
            'protocol' => 'CAN-BUS/MQTT',
            'api_key' => 'key_vario_esp32',
            'status' => 'standby',
        ]);

        TelemetryLog::create([
            'vehicle_id' => $vario->id,
            'timestamp' => $now->copy()->subHours(2),
            'engine_rpm' => 0,
            'speed_kmh' => 0.0,
            'engine_temp_c' => 60.0,
            'tps_throttle_percent' => 0.0,
            'gear_position' => 0,
            'battery_voltage_v' => 12.3,
            'stator_charging_amp' => 0.0,
            'oil_health_percent' => 24.0, // Low oil
            'oil_pressure_kpa' => 0.0,
            'o2_sensor_v' => 0.45,
            'map_sensor_kpa' => 101.3,
            'front_tire_psi' => 21.5, // Flat
            'rear_tire_psi' => 29.0,
            'brake_pad_front_percent' => 40.0,
            'brake_pad_rear_percent' => 35.0,
            'fuel_level_percent' => 50.0,
            'ambient_temp_c' => 30.0,
            'sidestand_down' => true,
            'lean_angle_deg' => 0.0,
            'odometer_km' => 27800.0,
        ]);

        // 4. Riwayat Servis (Maintenance Records)
        MaintenanceRecord::create([
            'vehicle_id' => $nmax->id,
            'service_type' => 'Servis Berkala & Ganti Oli Yamalube Super Sport',
            'service_date' => '2026-08-15',
            'odometer_km' => 7000.0,
            'cost_idr' => 185000,
            'workshop_name' => 'Yamaha Flagship Shop',
            'notes' => 'Filter oli diganti, busi dibersihkan.',
        ]);

        MaintenanceRecord::create([
            'vehicle_id' => $nmax->id,
            'service_type' => 'Ganti Oli Gardan & Servis CVT',
            'service_date' => '2026-06-20',
            'odometer_km' => 5000.0,
            'cost_idr' => 120000,
            'workshop_name' => 'Yamaha Flagship Shop',
            'notes' => 'Roller dan v-belt masih tebal.',
        ]);

        MaintenanceRecord::create([
            'vehicle_id' => $pcx->id,
            'service_type' => 'Servis Injeksi & Ganti Busi NGK',
            'service_date' => '2026-07-10',
            'odometer_km' => 16000.0,
            'cost_idr' => 210000,
            'workshop_name' => 'AHASS Mitra Honda',
            'notes' => 'Kalibrasi throttle body.',
        ]);
    }
}
