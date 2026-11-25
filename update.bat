@echo off
title MOTEC - Auto Update Project
echo ==============================================================================
echo                      MOTEC AUTO-UPDATE SCRIPT
echo ==============================================================================
echo.

echo [1/4] Menarik perubahan kodingan terbaru dari GitHub...
git pull origin main
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Gagal menarik perubahan dari Git. Pastikan koneksi internet aktif.
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo [2/4] Memeriksa dependensi PHP Composer...
call composer install --no-interaction

echo.
echo [3/4] Menjalankan migrasi database terbaru...
php artisan migrate --force

echo.
echo [4/4] Memperbarui asset frontend...
call npm install
call npm run build

echo.
echo ==============================================================================
echo [SUKSES] Kodingan MOTEC berhasil diperbarui ke versi terbaru!
echo ==============================================================================
echo.
pause
