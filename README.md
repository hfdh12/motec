# 🏍️ MOTEC — Motor Technical Check & IoT OBD-II Telemetry

Sistem pengecekan teknis dan telemetri sepeda motor injeksi mandiri terintegrasi sensor ECU real-time dan scanner **ELM327 OBD-II**.

---

## 🚀 Panduan Memulai Cepat (Untuk Tim / Kolaborator)

Ikuti langkah-langkah berikut untuk mengunduh dan menjalankan proyek ini di komputer Anda:

### 1. Prasyarat Sistem
Pastikan komputer Anda sudah terpasang:
- **PHP** $\ge$ 8.2 (disarankan menggunakan [Laragon](https://laragon.org/) atau XAMPP)
- **Composer** ([getcomposer.org](https://getcomposer.org/))
- **Node.js** $\ge$ 18 & **npm** ([nodejs.org](https://nodejs.org/))
- **MySQL Server** (bawaan Laragon/XAMPP port 3306)

---

### 2. Langkah Instalasi Proyek

Buka terminal (Git Bash / PowerShell / Command Prompt), lalu jalankan:

```bash
# 1. Clone repository dari GitHub
git clone https://github.com/hfdh12/motec.git
cd motec

# 2. Pasang dependensi PHP (Composer)
composer install

# 3. Buat file konfigurasi environment
copy .env.example .env
# (Di Linux / macOS gunakan: cp .env.example .env)

# 4. Generate Application Encryption Key
php artisan key:generate

# 5. Konfigurasi Database di file .env
# Buka file .env dan pastikan database sesuai:
# DB_DATABASE=db_motec
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Buat Database & Jalankan Migrasi + Data Awal (Seeder)
php artisan migrate --seed

# 7. Pasang dependensi Frontend & Compile Assets
npm install
npm run build

# 8. Jalankan Server Development
php artisan serve
```

Aplikasi siap dibuka di browser: **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

---

## 🔄 Cara Auto-Update Ketika Ada Perubahan Kodingan

### A. Untuk Anggota Tim Mengambil Kodingan Terbaru
Cukup jalankan file `update.bat` (atau ketik perintah di bawah):
```bash
git pull origin main
php artisan migrate
npm run build
```

### B. Auto-Refresh Browser Saat Menulis Kode (Hot Reloading)
Jika Anda sedang mengedit file tampilan (Blade, CSS, atau JavaScript) dan ingin browser otomatis refresh tanpa tekan F5:
```bash
npm run dev
```

---

## 🔌 Fitur Utama & Navigasi Halaman

- **Dashboard:** `http://127.0.0.1:8000/` — Ringkasan kondisi motor, skor kesehatan, status aki, dan riwayat servis.
- **Konektor ELM327 OBD-II:** `http://127.0.0.1:8000/elm327` — Koneksi langsung ke scanner hardware ELM327 via Web Serial (USB/Bluetooth) & WiFi.
- **Live Cockpit HUD:** `http://127.0.0.1:8000/live-hud` — Tachometer gauge, speedometer, dan indikator shift light real-time.
- **Scanner DTC (Trouble Codes):** `http://127.0.0.1:8000/dtc-scanner` — Pemindaian memori kerusakan sensor ECU (Mode 03) dan reset lampu MIL (Mode 04).
- **Simulator Virtual ECU:** `http://127.0.0.1:8000/simulator` — Alat uji coba injeksi sensor telemetri tanpa motor fisik.

---

## 🛠️ Menghubungkan Hardware ELM327 OBD-II

### Opsi 1: Lewat Browser Langsung (Web Serial)
1. Pasang scanner ELM327 ke soket OBD2 motor.
2. Putar kontak motor ke posisi **ON**.
3. Buka halaman `/elm327` di Google Chrome atau Microsoft Edge.
4. Klik tombol **"Hubungkan Port Serial ELM327"** dan pilih port COM adapter Anda.

### Opsi 2: Menggunakan Script Python Bridge
```bash
# Menggunakan USB / Bluetooth Serial COM:
python scripts/motec_elm327_bridge.py --port COM3 --baud 38400 --vehicle 1

# Mode Simulasi Demo (Uji coba tanpa motor):
python scripts/motec_elm327_bridge.py --demo --vehicle 1
```

---

## 👤 Akun Sampel
- **Email:** `rian.adi@motec.id`
- **Password:** `secret123`
