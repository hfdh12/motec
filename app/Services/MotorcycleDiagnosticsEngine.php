<?php

namespace App\Services;

class MotorcycleDiagnosticsEngine
{
    /**
     * Evaluasi Kesehatan Teknis Motor Mandiri (MOTEC Diagnostic Evaluation Engine).
     *
     * @param  array  $telemetry
     * @param  array  $activeDtcs
     * @return array
     */
    public static function evaluate(array $telemetry = [], array $activeDtcs = []): array
    {
        $findings = [];
        $recommendations = [];
        $alerts = [];

        // -------------------------------------------------------------
        // 1. EVALUASI SISTEM KELISTRIKAN & PENGISIAN (Aki & Spul/Kiprok)
        // -------------------------------------------------------------
        $electricalScore = 100;
        $batt = (float) ($telemetry['battery_voltage_v'] ?? 12.6);
        $rpm = (int) ($telemetry['engine_rpm'] ?? 0);

        if ($rpm === 0) {
            // Kontak ON (Standby)
            if ($batt < 11.5) {
                $electricalScore -= 50;
                $alerts[] = [
                    'type' => 'critical',
                    'system' => 'Kelistrikan',
                    'msg' => sprintf('Aki Drop Kritis: %.1fV (Risiko motor mogok total)', $batt),
                ];
                $findings[] = sprintf('Tegangan aki sangat lemah (%.1fV saat kontak ON).', $batt);
                $recommendations[] = 'Segera lakukan pengisian daya (charging) atau ganti aki baru tipe MF/Gel.';
            } elseif ($batt < 12.2) {
                $electricalScore -= 25;
                $alerts[] = [
                    'type' => 'warning',
                    'system' => 'Kelistrikan',
                    'msg' => sprintf('Tegangan Aki Rendah: %.1fV', $batt),
                ];
                $findings[] = sprintf('Kapasitas aki mulai menurun (%.1fV).', $batt);
                $recommendations[] = 'Periksa kondisi air aki atau daya simpan baterai.';
            } else {
                $findings[] = sprintf('Tegangan standby aki prima (%.1fV).', $batt);
            }
        } else {
            // Mesin Hidup (Charging System Active)
            if ($batt < 12.8) {
                $electricalScore -= 40;
                $alerts[] = [
                    'type' => 'critical',
                    'system' => 'Pengisian Spul',
                    'msg' => sprintf('Sistem Pengisian Tidak Bekerja (%.1fV saat mesin menyala)', $batt),
                ];
                $findings[] = sprintf('Kiprok/spul tidak mengisi daya secara optimal (%.1fV saat %d RPM).', $batt, $rpm);
                $recommendations[] = 'Cek soket alternator/spul dan regulator rectifier (kiprok).';
            } elseif ($batt > 15.3) {
                $electricalScore -= 45;
                $alerts[] = [
                    'type' => 'critical',
                    'system' => 'Kiprok Overcharging',
                    'msg' => sprintf('Overcharge Berbahaya (%.1fV)', $batt),
                ];
                $findings[] = sprintf('Regulator kiprok bocor / overcharge (%.1fV).', $batt);
                $recommendations[] = 'Kiprok rusak berisiko merusak ECU, speedometer, dan membakar aki. Segera ganti kiprok.';
            } else {
                $findings[] = sprintf('Sistem pengisian bekerja normal (%.1fV).', $batt);
            }
        }

        // -------------------------------------------------------------
        // 2. EVALUASI SISTEM PENDINGINAN (Coolant / Engine Temperature)
        // -------------------------------------------------------------
        $coolingScore = 100;
        $temp = (float) ($telemetry['engine_temp_c'] ?? 85.0);

        if ($temp > 108.0) {
            $coolingScore -= 60;
            $alerts[] = [
                'type' => 'critical',
                'system' => 'Pendingin Mesin',
                'msg' => sprintf('Suhu Mesin Panas Kritis / OVERHEAT (%.1f°C)!', $temp),
            ];
            $findings[] = sprintf('Suhu mesin melebihi batas aman toleransi (%.1f°C).', $temp);
            $recommendations[] = 'Matikan mesin segera! Cek cairan reservoir radiator, kebocoran selang, dan fungsi relay kipas.';
        } elseif ($temp > 100.0) {
            $coolingScore -= 25;
            $alerts[] = [
                'type' => 'warning',
                'system' => 'Pendingin Mesin',
                'msg' => sprintf('Suhu Mesin Tinggi (%.1f°C)', $temp),
            ];
            $findings[] = sprintf('Suhu mesin mendekati batas atas kerja (%.1f°C).', $temp);
            $recommendations[] = 'Pastikan kipas pendingin radiator berputar otomatis dan kisi-kisi radiator tidak tertutup lumpur.';
        } elseif ($temp < 50.0 && $rpm > 0) {
            $findings[] = sprintf('Mesin dalam fase pemanasan awal (%.1f°C).', $temp);
        } else {
            $findings[] = sprintf('Suhu kerja mesin ideal (%.1f°C).', $temp);
        }

        // -------------------------------------------------------------
        // 3. EVALUASI SISTEM MESIN & INJEKSI (Engine, TPS, O2 Sensor)
        // -------------------------------------------------------------
        $engineScore = 100;
        $tps = (float) ($telemetry['tps_throttle_percent'] ?? 0.0);
        $o2_v = (float) ($telemetry['o2_sensor_v'] ?? 0.45);

        if ($rpm > 0 && $rpm < 1100 && $tps < 2.0) {
            $engineScore -= 15;
            $alerts[] = [
                'type' => 'warning',
                'system' => 'Mesin',
                'msg' => sprintf('Putaran Langsam Terlalu Rendah (%d RPM)', $rpm),
            ];
            $findings[] = sprintf('RPM stasioner (idle) di bawah standar pabrik (%d RPM).', $rpm);
            $recommendations[] = 'Lakukan kalibrasi IACV (Idle Air Control Valve) dan bersihkan throttle body.';
        } elseif ($rpm > 2300 && $tps < 1.0) {
            $engineScore -= 20;
            $alerts[] = [
                'type' => 'warning',
                'system' => 'Mesin',
                'msg' => sprintf('Langsam Tertahan Tinggi (%d RPM pada gas tertutup)', $rpm),
            ];
            $findings[] = 'Gejala gas menggantung (high idle).';
            $recommendations[] = 'Periksa kabel gas, intake manifold dari kebocoran udara palsu, atau sensor TPS.';
        } else {
            $findings[] = sprintf('Putaran mesin responsif (%d RPM, TPS %.1f%%).', $rpm, $tps);
        }

        if ($o2_v < 0.1) {
            $engineScore -= 15;
            $findings[] = sprintf('Sensor O2 membaca campuran terlalu kurus/lean (%.2fV).', $o2_v);
            $recommendations[] = 'Periksa tekanan fuel pump dan kemungkinan filter bensin tersumbat.';
        } elseif ($o2_v > 0.9) {
            $engineScore -= 15;
            $findings[] = sprintf('Sensor O2 membaca campuran terlalu gemuk/rich (%.2fV).', $o2_v);
            $recommendations[] = 'Periksa kondisi filter udara (kotor) atau injektor bensin menetes.';
        }

        // -------------------------------------------------------------
        // 4. EVALUASI SISTEM PELUMASAN (Oli Mesin)
        // -------------------------------------------------------------
        $lubricationScore = 100;
        $oilHealth = (float) ($telemetry['oil_health_percent'] ?? 85.0);
        $oilKpa = (float) ($telemetry['oil_pressure_kpa'] ?? 250.0);

        if ($oilHealth < 20.0) {
            $lubricationScore -= 50;
            $alerts[] = [
                'type' => 'critical',
                'system' => 'Pelumasan',
                'msg' => sprintf('Kualitas Oli Kritis (%.0f%% sisa masa pakai)', $oilHealth),
            ];
            $findings[] = sprintf('Masa pakai oli hampir habis (%.0f%%).', $oilHealth);
            $recommendations[] = 'Wajib ganti oli mesin dan filter oli sekarang untuk mencegah keausan noken as & piston.';
        } elseif ($oilHealth < 40.0) {
            $lubricationScore -= 20;
            $alerts[] = [
                'type' => 'warning',
                'system' => 'Pelumasan',
                'msg' => sprintf('Oli Mesin Mulai Menurun (%.0f%%)', $oilHealth),
            ];
            $findings[] = sprintf('Kualitas pelumasan menurun (%.0f%%).', $oilHealth);
            $recommendations[] = 'Jadwalkan penggantian oli mesin dalam 500 km ke depan.';
        } else {
            $findings[] = sprintf('Kualitas pelumasan oli mesin baik (%.0f%%).', $oilHealth);
        }

        if ($rpm > 2000 && $oilKpa < 100.0) {
            $lubricationScore -= 40;
            $alerts[] = [
                'type' => 'critical',
                'system' => 'Tekanan Oli',
                'msg' => sprintf('Tekanan Oli Rendah (%.0f kPa)', $oilKpa),
            ];
            $findings[] = 'Tekanan sirkulasi oli di bawah batas aman.';
            $recommendations[] = 'Cek volume oli mesin (kemungkinan berkurang/bocor) dan fungsi pompa oli.';
        }

        // -------------------------------------------------------------
        // 5. EVALUASI BAN & PENGEREMAN (Safety: Tires & Brakes)
        // -------------------------------------------------------------
        $safetyScore = 100;
        $frontPsi = (float) ($telemetry['front_tire_psi'] ?? 29.0);
        $rearPsi = (float) ($telemetry['rear_tire_psi'] ?? 33.0);
        $frontBrake = (float) ($telemetry['brake_pad_front_percent'] ?? 90.0);
        $rearBrake = (float) ($telemetry['brake_pad_rear_percent'] ?? 90.0);

        if ($frontPsi < 24.0) {
            $safetyScore -= 20;
            $alerts[] = [
                'type' => 'warning',
                'system' => 'Ban Depan',
                'msg' => sprintf('Tekanan Ban Depan Kurang (%.1f PSI)', $frontPsi),
            ];
            $findings[] = sprintf('Ban depan kempes (%.1f PSI), setang berpotensi berat dan velg rawan peyang.', $frontPsi);
            $recommendations[] = 'Tambah angin ban depan ke 29 PSI (atau standar pabrik).';
        } elseif ($frontPsi > 36.0) {
            $safetyScore -= 10;
            $findings[] = sprintf('Tekanan ban depan terlalu keras (%.1f PSI).', $frontPsi);
            $recommendations[] = 'Kurangi tekanan ban depan ke 29 PSI untuk grip pengereman maksimal.';
        }

        if ($rearPsi < 26.0) {
            $safetyScore -= 20;
            $alerts[] = [
                'type' => 'warning',
                'system' => 'Ban Belakang',
                'msg' => sprintf('Tekanan Ban Belakang Kurang (%.1f PSI)', $rearPsi),
            ];
            $findings[] = sprintf('Ban belakang kempes (%.1f PSI), konsumsi bensin boros dan motor oleng.', $rearPsi);
            $recommendations[] = 'Tambah angin ban belakang ke 33-36 PSI.';
        }

        if ($frontBrake < 20.0 || $rearBrake < 20.0) {
            $safetyScore -= 30;
            $alerts[] = [
                'type' => 'critical',
                'system' => 'Pengereman',
                'msg' => sprintf('Kampas Rem Tipis Kritis (Depan: %.0f%%, Belakang: %.0f%%)', $frontBrake, $rearBrake),
            ];
            $findings[] = 'Ketebalan kampas rem mendekati batas aus minimum.';
            $recommendations[] = 'Ganti kampas rem segera agar piringan cakram / tromol tidak tergores besi.';
        } elseif ($frontBrake < 35.0 || $rearBrake < 35.0) {
            $safetyScore -= 15;
            $findings[] = sprintf('Kampas rem mulai menipis (Depan: %.0f%%, Belakang: %.0f%%).', $frontBrake, $rearBrake);
            $recommendations[] = 'Periksa kondisi kampas rem saat servis berkala berikutnya.';
        }

        // -------------------------------------------------------------
        // 6. PENURUNAN SKOR AKIBAT DTC (Diagnostic Trouble Codes)
        // -------------------------------------------------------------
        $dtcPenalty = 0;
        foreach ($activeDtcs as $dtc) {
            $sev = strtolower($dtc['severity'] ?? 'warning');
            $code = $dtc['code'] ?? ($dtc['dtc_code'] ?? 'UNKNOWN');
            $title = $dtc['title_id'] ?? 'Gangguan Terdeteksi';

            if ($sev === 'critical') {
                $dtcPenalty += 25;
                $engineScore -= 20;
                $electricalScore -= 15;
                $alerts[] = [
                    'type' => 'critical',
                    'system' => 'ECU Fault Code',
                    'msg' => sprintf('Kode DTC Kritis %s: %s', $code, $title),
                ];
            } else {
                $dtcPenalty += 12;
                $engineScore -= 10;
                $alerts[] = [
                    'type' => 'warning',
                    'system' => 'ECU Fault Code',
                    'msg' => sprintf('Kode DTC %s: %s', $code, $title),
                ];
            }

            $findings[] = sprintf('DTC Aktif [%s]: %s.', $code, $title);
            if (!empty($dtc['recommendation_id'])) {
                $recommendations[] = sprintf('[%s] %s', $code, $dtc['recommendation_id']);
            }
        }

        // Normalisasi Sub-skor (Clamping 10 - 100)
        $electricalScore = max(10, min(100, $electricalScore));
        $coolingScore = max(10, min(100, $coolingScore));
        $engineScore = max(10, min(100, $engineScore));
        $lubricationScore = max(10, min(100, $lubricationScore));
        $safetyScore = max(10, min(100, $safetyScore));

        // Bobot Total Skor (Weighting)
        // Mesin: 25%, Kelistrikan: 25%, Pendingin: 15%, Pelumasan: 15%, Keselamatan/Ban: 20%
        $rawTotal = (
            ($engineScore * 0.25) +
            ($electricalScore * 0.25) +
            ($coolingScore * 0.15) +
            ($lubricationScore * 0.15) +
            ($safetyScore * 0.20)
        ) - $dtcPenalty;

        $totalScore = (int) max(10, min(100, round($rawTotal)));

        // Status Diagnosis
        if ($totalScore >= 90) {
            $status = 'EXCELLENT';
            $statusText = 'Kondisi Prima & Sangat Baik';
            $statusBadge = 'success';
        } elseif ($totalScore >= 75) {
            $status = 'GOOD';
            $statusText = 'Kondisi Baik & Siap Pakai';
            $statusBadge = 'info';
        } elseif ($totalScore >= 55) {
            $status = 'ATTENTION_NEEDED';
            $statusText = 'Perlu Perawatan / Servis Ringan';
            $statusBadge = 'warning';
        } else {
            $status = 'CRITICAL';
            $statusText = 'Kondisi Kritis - Wajib Perbaikan Segera';
            $statusBadge = 'danger';
        }

        return [
            'total_score' => $totalScore,
            'status' => $status,
            'status_text' => $statusText,
            'status_badge' => $statusBadge,
            'sub_scores' => [
                'engine' => $engineScore,
                'electrical' => $electricalScore,
                'cooling' => $coolingScore,
                'lubrication' => $lubricationScore,
                'safety' => $safetyScore,
            ],
            'alerts' => $alerts,
            'findings' => $findings,
            'recommendations' => array_values(array_unique($recommendations)),
            'detected_dtc_count' => count($activeDtcs),
            'telemetry_snapshot' => $telemetry,
        ];
    }
}
