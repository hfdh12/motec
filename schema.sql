-- ==============================================================================
-- MOTEC - MOTOR TECHNICAL CHECK
-- Database Schema for IoT Motorcycle Telemetry & Technical Diagnostics
-- Compatible with MySQL 8.0+, MariaDB 10.5+, and SQLite 3.30+
-- ==============================================================================

-- 1. Tabel Pengguna (Users)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30),
    role VARCHAR(20) DEFAULT 'owner', -- 'owner', 'technician', 'admin'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Data Sepeda Motor (Vehicles)
CREATE TABLE IF NOT EXISTS vehicles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plate_number VARCHAR(20) NOT NULL UNIQUE,
    brand VARCHAR(60) NOT NULL,           -- contoh: Yamaha, Honda, Kawasaki, Suzuki, Vespa
    model VARCHAR(100) NOT NULL,          -- contoh: NMAX 155 VVA, PCX 160, ZX-25R, Vario 160
    year INTEGER NOT NULL,
    vin_chassis VARCHAR(50) UNIQUE,       -- Nomor Rangka
    engine_number VARCHAR(50),           -- Nomor Mesin
    engine_displacement_cc INTEGER NOT NULL DEFAULT 150,
    cooling_type VARCHAR(30) DEFAULT 'liquid', -- 'liquid', 'air'
    fuel_capacity_liter REAL DEFAULT 6.5,
    current_odometer_km REAL DEFAULT 0.0,
    telematics_unit_id VARCHAR(64) UNIQUE, -- ID perangkat IoT / OBD2 / ESP32 yang terpasang di motor
    is_connected BOOLEAN DEFAULT 0,
    last_ping_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Perangkat IoT / Telematika Terhubung (Telematics Devices)
CREATE TABLE IF NOT EXISTS telematics_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_id INTEGER,
    device_uid VARCHAR(64) NOT NULL UNIQUE, -- Serial / MAC Address ESP32/OBD-II
    device_model VARCHAR(60) DEFAULT 'MOTEC-IoT-ESP32-V2',
    firmware_version VARCHAR(30) DEFAULT 'v2.4.1',
    protocol VARCHAR(30) DEFAULT 'MQTT/HTTP', -- 'CAN-BUS', 'K-LINE', 'OBD2-ELM327', 'BLE'
    api_key VARCHAR(100) NOT NULL,
    battery_backup_v REAL DEFAULT 3.7,
    status VARCHAR(20) DEFAULT 'active',    -- 'active', 'standby', 'offline'
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL
);

-- 4. Master Kamus Kode Gangguan Standar Motor (Diagnostic Trouble Codes - DTC)
CREATE TABLE IF NOT EXISTS dtc_library (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(15) NOT NULL UNIQUE,      -- contoh: P0115, P0105, P0562, P0120, P0335
    category VARCHAR(50) NOT NULL,         -- Powertrain, Electrical, Sensor, Fuel, Cooling
    title_id VARCHAR(150) NOT NULL,        -- Bahasa Indonesia
    title_en VARCHAR(150) NOT NULL,
    severity VARCHAR(20) DEFAULT 'warning',-- 'info', 'warning', 'critical'
    description_id TEXT NOT NULL,
    recommendation_id TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Tabel Telemetri Sensor Real-Time (Telemetry Logs)
-- Menyimpan record data sensor yang dikirim oleh perangkat IoT di motor
CREATE TABLE IF NOT EXISTS telemetry_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_id INTEGER NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Parameter Mesin & Performa
    engine_rpm INTEGER NOT NULL DEFAULT 0,
    speed_kmh REAL NOT NULL DEFAULT 0.0,
    engine_temp_c REAL NOT NULL DEFAULT 0.0,     -- Suhu Mesin / Coolant (°C)
    tps_throttle_percent REAL DEFAULT 0.0,      -- Posisi Bukaan Gas (%)
    gear_position INTEGER DEFAULT 0,            -- 0=N, 1, 2, 3, 4, 5, 6
    
    -- Parameter Kelistrikan
    battery_voltage_v REAL NOT NULL DEFAULT 12.6,-- Tegangan Aki (Volt)
    stator_charging_amp REAL DEFAULT 0.0,       -- Arus Pengisian Kiprok/Spul (Ampere)
    
    -- Parameter Pelumasan & Sensor
    oil_health_percent REAL DEFAULT 100.0,      -- Estimasi Kualitas Oli (%)
    oil_pressure_kpa REAL DEFAULT 250.0,        -- Tekanan Oli
    o2_sensor_v REAL DEFAULT 0.45,              -- Sensor O2 Lambda (Volt)
    map_sensor_kpa REAL DEFAULT 101.3,          -- Manifold Absolute Pressure
    
    -- Parameter Keamanan & Fisik
    front_tire_psi REAL DEFAULT 29.0,           -- Tekanan Ban Depan (PSI)
    rear_tire_psi REAL DEFAULT 33.0,            -- Tekanan Ban Belakang (PSI)
    brake_pad_front_percent REAL DEFAULT 95.0,  -- Ketebalan Kampas Rem Depan (%)
    brake_pad_rear_percent REAL DEFAULT 95.0,   -- Ketebalan Kampas Rem Belakang (%)
    fuel_level_percent REAL DEFAULT 80.0,       -- Level Bensin (%)
    
    -- Kondisi Lingkungan & Status
    ambient_temp_c REAL DEFAULT 30.0,
    sidestand_down BOOLEAN DEFAULT 0,           -- Standar Samping Aktif/Tidak
    lean_angle_deg REAL DEFAULT 0.0,            -- Kemiringan Motor
    odometer_km REAL NOT NULL,
    raw_payload TEXT,                           -- JSON mentah dari hardware
    
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- 6. Riwayat Sesi Pengecekan Teknis Lengkap (Diagnostic Sessions)
CREATE TABLE IF NOT EXISTS diagnostic_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_id INTEGER NOT NULL,
    session_uid VARCHAR(64) NOT NULL UNIQUE,
    total_score INTEGER NOT NULL,               -- Skor Kondisi Keseluruhan (0 - 100%)
    engine_score INTEGER NOT NULL,              -- Skor Mesin (0 - 100)
    electrical_score INTEGER NOT NULL,          -- Skor Kelistrikan (0 - 100)
    cooling_score INTEGER NOT NULL,             -- Skor Sistem Pendingin (0 - 100)
    lubrication_score INTEGER NOT NULL,         -- Skor Pelumasan (0 - 100)
    safety_score INTEGER NOT NULL,              -- Skor Rem & Ban (0 - 100)
    status VARCHAR(30) NOT NULL,                -- 'EXCELLENT', 'GOOD', 'ATTENTION_NEEDED', 'CRITICAL'
    detected_dtc_count INTEGER DEFAULT 0,
    findings_summary TEXT,                      -- Ringkasan hasil temuan
    action_items TEXT,                          -- Tindakan perbaikan yang disarankan (JSON/Text)
    tested_by VARCHAR(50) DEFAULT 'motec-ai-scanner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- 7. Log Kerusakan / DTC Aktif pada Motor (Vehicle Faults)
CREATE TABLE IF NOT EXISTS vehicle_fault_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_id INTEGER NOT NULL,
    dtc_code VARCHAR(15) NOT NULL,
    session_id INTEGER,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    cleared_at TIMESTAMP,
    cleared_by VARCHAR(60),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (dtc_code) REFERENCES dtc_library(code)
);

-- 8. Riwayat Servis & Perawatan (Maintenance Records)
CREATE TABLE IF NOT EXISTS maintenance_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_id INTEGER NOT NULL,
    service_type VARCHAR(100) NOT NULL,         -- Ganti Oli, Ganti Busi, Kuras Radiator, Servis CVT, Ganti Aki
    service_date DATE NOT NULL,
    odometer_km REAL NOT NULL,
    cost_idr INTEGER DEFAULT 0,
    workshop_name VARCHAR(120),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- Indeks Performa untuk Pencarian Cepat & Real-Time Query
CREATE INDEX IF NOT EXISTS idx_telemetry_vehicle_ts ON telemetry_logs(vehicle_id, timestamp DESC);
CREATE INDEX IF NOT EXISTS idx_vehicle_plate ON vehicles(plate_number);
CREATE INDEX IF NOT EXISTS idx_device_uid ON telematics_devices(device_uid);
CREATE INDEX IF NOT EXISTS idx_diag_vehicle ON diagnostic_sessions(vehicle_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_faults_vehicle ON vehicle_fault_logs(vehicle_id, is_active);
