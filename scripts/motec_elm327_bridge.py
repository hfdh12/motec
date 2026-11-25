#!/usr/bin/env python3
"""
==============================================================================
MOTEC (Motor Technical Check) - ELM327 OBD-II Telemetry Bridge
==============================================================================
Script jembatan telemetri Python mandiri untuk menghubungkan adapter ELM327
(USB, Bluetooth Serial COM, atau WiFi TCP/IP) langsung ke sistem MOTEC.

Penggunaan:
    # 1. Menggunakan port serial USB / Bluetooth:
    python motec_elm327_bridge.py --port COM3 --baud 38400 --vehicle 1

    # 2. Menggunakan adapter ELM327 WiFi:
    python motec_elm327_bridge.py --wifi 192.168.0.10:35000 --vehicle 1

    # 3. Mode Uji Coba Virtual (Demo Tanpa Hardware Fisik):
    python motec_elm327_bridge.py --demo --vehicle 1
==============================================================================
"""

import argparse
import json
import math
import re
import socket
import sys
import time
import urllib.request
import urllib.error

# Coba import pyserial jika tersedia
try:
    import serial
    import serial.tools.list_ports
    HAS_SERIAL = True
except ImportError:
    HAS_SERIAL = False


class Elm327Connection:
    """Kelas abstraksi komunikasi serial atau socket TCP untuk ELM327."""

    def __init__(self, port=None, baud=38400, wifi=None, demo=False):
        self.port = port
        self.baud = baud
        self.wifi = wifi
        self.demo = demo
        self.ser = None
        self.sock = None
        self.protocol = "Unknown"
        self.battery_v = 12.6

    def connect(self):
        if self.demo:
            print("[INFO] Berjalan dalam mode DEMO SIMULASI (tanpa hardware fisik).")
            return True

        if self.wifi:
            parts = self.wifi.split(":")
            host = parts[0]
            port = int(parts[1]) if len(parts) > 1 else 35000
            print(f"[INFO] Menghubungkan ke ELM327 WiFi di {host}:{port}...")
            self.sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            self.sock.settimeout(3.0)
            self.sock.connect((host, port))
            print("[OK] Terhubung ke socket TCP ELM327.")
            return True

        if not HAS_SERIAL:
            print("[ERROR] Modul 'pyserial' belum terpasang.")
            print("        Silakan jalankan: pip install pyserial")
            return False

        if not self.port:
            ports = list(serial.tools.list_ports.comports())
            if not ports:
                print("[ERROR] Tidak ada port COM / serial yang terdeteksi.")
                return False
            self.port = ports[0].device
            print(f"[INFO] Menggunakan port serial otomatis: {self.port}")

        print(f"[INFO] Membuka port serial {self.port} pada {self.baud} baud...")
        self.ser = serial.Serial(self.port, self.baud, timeout=1.5)
        time.sleep(0.5)
        print("[OK] Port serial terbuka.")
        return True

    def send_cmd(self, cmd, timeout=2.0):
        if self.demo:
            return self._demo_response(cmd)

        clean_cmd = cmd.strip() + "\r"
        if self.ser:
            self.ser.reset_input_buffer()
            self.ser.write(clean_cmd.encode("latin-1"))
            raw = self._read_until_prompt(self.ser, timeout)
            return raw.decode("latin-1", errors="ignore").strip()

        if self.sock:
            self.sock.sendall(clean_cmd.encode("latin-1"))
            raw = b""
            start = time.time()
            while time.time() - start < timeout:
                chunk = self.sock.recv(64)
                if not chunk:
                    break
                raw += chunk
                if b">" in raw:
                    break
            return raw.decode("latin-1", errors="ignore").strip()

        return ""

    def _read_until_prompt(self, ser, timeout):
        res = bytearray()
        start = time.time()
        while time.time() - start < timeout:
            if ser.in_waiting:
                c = ser.read(1)
                if not c:
                    break
                res.extend(c)
                if c == b">":
                    break
            else:
                time.sleep(0.02)
        return bytes(res)

    def init_elm327(self):
        """Inisialisasi handshake standar ELM327 AT Commands."""
        print("[1/5] Mengirim ATZ (Reset adapter)...")
        r = self.send_cmd("ATZ")
        print(f"      Respons: {r}")
        time.sleep(0.5)

        print("[2/5] Mengatur opsi ATE0 (Echo off) & ATL0 (Linefeed off)...")
        self.send_cmd("ATE0")
        self.send_cmd("ATL0")
        self.send_cmd("ATS0")  # Spaces off

        print("[3/5] Mengatur protokol ATSP0 (Auto Protocol)...")
        self.send_cmd("ATSP0")
        dp = self.send_cmd("ATDP")
        self.protocol = dp.replace("OK", "").replace("SEARCHING", "").strip() or "ISO 15765-4 CAN"
        print(f"      Protokol terdeteksi: {self.protocol}")

        print("[4/5] Membaca voltase sistem baterai (ATRV)...")
        rv = self.send_cmd("ATRV")
        m = re.search(r"([0-9]+\.?[0-9]*)", rv)
        if m:
            self.battery_v = float(m.group(1))
        print(f"      Voltase Aki Motor: {self.battery_v} V")

        print("[5/5] Menguji komunikasi ECU dengan PID 0100...")
        p0100 = self.send_cmd("0100")
        print(f"      Respons 0100: {p0100}")
        if "UNABLE" in p0100 or "NO DATA" in p0100:
            print("[WARN] ECU belum merespon. Pastikan kunci kontak motor dalam posisi ON!")
        else:
            print("[OK] Komunikasi ECU motor aktif dan siap membaca sensor!")

    def _demo_response(self, cmd):
        cmd = cmd.strip().upper()
        t = time.time()
        if cmd == "ATZ":
            return "ELM327 v1.5"
        if cmd == "ATE0" or cmd == "ATL0" or cmd == "ATS0" or cmd == "ATSP0":
            return "OK"
        if cmd == "ATDP":
            return "ISO 15765-4 (CAN 11/500)"
        if cmd == "ATRV":
            return "14.2V"
        if cmd == "0100":
            return "4100BE3FB811"
        if cmd == "010C":  # RPM
            rpm = int(1800 + math.sin(t) * 1000 + 40)
            raw = rpm * 4
            a = (raw >> 8) & 0xFF
            b = raw & 0xFF
            return f"410C{a:02X}{b:02X}"
        if cmd == "010D":  # Speed
            spd = max(0, int((math.sin(t) + 1) * 35))
            return f"410D{spd:02X}"
        if cmd == "0105":  # Coolant ECT
            ect = 84 + int(math.sin(t * 0.1) * 3)
            return f"4105{(ect + 40):02X}"
        if cmd == "0111":  # Throttle TPS
            tps = max(0, int((math.sin(t) + 1) * 30))
            raw = int((tps * 255) / 100)
            return f"4111{raw:02X}"
        if cmd == "03":  # DTC
            return "4301150562"
        return "NO DATA"


def decode_pid(raw_resp):
    """Decode string respons hex OBD-II ke angka metrik."""
    clean = re.sub(r"[^0-9A-Fa-f]", "", raw_resp).upper()
    res = {}

    # Mode 01
    if "410C" in clean:
        idx = clean.index("410C") + 4
        hex_data = clean[idx : idx + 4]
        if len(hex_data) == 4:
            a = int(hex_data[0:2], 16)
            b = int(hex_data[2:4], 16)
            res["engine_rpm"] = round(((a * 256) + b) / 4)

    if "410D" in clean:
        idx = clean.index("410D") + 4
        hex_data = clean[idx : idx + 2]
        if len(hex_data) == 2:
            res["speed_kmh"] = float(int(hex_data, 16))

    if "4105" in clean:
        idx = clean.index("4105") + 4
        hex_data = clean[idx : idx + 2]
        if len(hex_data) == 2:
            res["engine_temp_c"] = float(int(hex_data, 16) - 40)

    if "4111" in clean:
        idx = clean.index("4111") + 4
        hex_data = clean[idx : idx + 2]
        if len(hex_data) == 2:
            res["tps_throttle_percent"] = round((int(hex_data, 16) * 100.0) / 255.0, 1)

    return res


def send_to_motec(api_url, payload):
    """Mengirim telemetri yang diekstrak dari ELM327 ke MOTEC API Ingest."""
    data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        api_url,
        data=data,
        headers={"Content-Type": "application/json", "Accept": "application/json"},
    )
    try:
        with urllib.request.urlopen(req, timeout=1.5) as resp:
            return resp.status == 200
    except Exception as e:
        return False


def main():
    parser = argparse.ArgumentParser(
        description="MOTEC ELM327 OBD-II Motorcycle Telemetry Bridge"
    )
    parser.add_argument("--port", help="Port serial (misal: COM3, /dev/ttyUSB0)")
    parser.add_argument("--baud", type=int, default=38400, help="Baud rate (default: 38400)")
    parser.add_argument("--wifi", help="Host WiFi ELM327 (misal: 192.168.0.10:35000)")
    parser.add_argument("--vehicle", type=int, default=1, help="ID Kendaraan MOTEC (default: 1)")
    parser.add_argument(
        "--url",
        default="http://127.0.0.1:8000/api/telemetry/ingest",
        help="URL Endpoint Ingest MOTEC",
    )
    parser.add_argument(
        "--demo", action="store_true", help="Gunakan mode simulasi virtual tanpa hardware"
    )
    args = parser.parse_args()

    print("==================================================================")
    print("      🏍️  MOTEC - ELM327 OBD-II MOTORCYCLE TELEMETRY BRIDGE       ")
    print("==================================================================")
    print(f"Target MOTEC API : {args.url}")
    print(f"Vehicle Profile  : ID {args.vehicle}")
    print("------------------------------------------------------------------")

    elm = Elm327Connection(port=args.port, baud=args.baud, wifi=args.wifi, demo=args.demo)
    if not elm.connect():
        sys.exit(1)

    elm.init_elm327()

    print("\n[INFO] Memulai loop pembacaan data sensor kontinu (Ctrl+C untuk keluar)...")
    last_dtc_check = 0

    try:
        while True:
            t0 = time.time()
            telemetry = {
                "vehicle_id": args.vehicle,
                "battery_voltage_v": elm.battery_v,
            }

            # 1. Baca RPM (010C)
            r_rpm = elm.send_cmd("010C")
            telemetry.update(decode_pid(r_rpm))

            # 2. Baca Kecepatan (010D)
            r_spd = elm.send_cmd("010D")
            telemetry.update(decode_pid(r_spd))

            # 3. Baca Suhu Mesin ECT (0105)
            r_ect = elm.send_cmd("0105")
            telemetry.update(decode_pid(r_ect))

            # 4. Baca Bukaan Gas TPS (0111)
            r_tps = elm.send_cmd("0111")
            telemetry.update(decode_pid(r_tps))

            # 5. Cek DTC tiap 30 detik
            if time.time() - last_dtc_check > 30:
                last_dtc_check = time.time()
                r_dtc = elm.send_cmd("03")
                clean_dtc = re.sub(r"[^0-9A-Fa-f]", "", r_dtc).upper()
                if "43" in clean_dtc and len(clean_dtc) > 2:
                    sub = clean_dtc[clean_dtc.index("43") + 2 :]
                    dtcs = []
                    for i in range(0, len(sub) - 3, 4):
                        pair = sub[i : i + 4]
                        if pair != "0000":
                            b1 = int(pair[0:2], 16)
                            prefix = ["P", "C", "B", "U"][(b1 & 0xC0) >> 6]
                            code = f"{prefix}{(b1 & 0x30) >> 4}{hex(b1 & 0x0F)[2:].upper()}{pair[2:4]}"
                            dtcs.append(code)
                    if dtcs:
                        telemetry["dtc_codes"] = list(set(dtcs))
                        print(f"[⚠️ DTC AKTIF TERBACA] {dtcs}")

            # Kirim ke MOTEC
            ok = send_to_motec(args.url, telemetry)
            status_symbol = "🟢" if ok else "🔴"

            print(
                f"\r{status_symbol} [MOTEC SYNC] "
                f"RPM: {telemetry.get('engine_rpm', 0):>5} | "
                f"Speed: {telemetry.get('speed_kmh', 0):>3.0f} km/h | "
                f"ECT: {telemetry.get('engine_temp_c', 0):>3.0f}°C | "
                f"TPS: {telemetry.get('tps_throttle_percent', 0):>3.0f}% | "
                f"Volt: {telemetry.get('battery_voltage_v', 12.6):>4.1f}V",
                end="",
                flush=True,
            )

            # Polling delay ~300ms
            elapsed = time.time() - t0
            time.sleep(max(0.05, 0.3 - elapsed))

    except KeyboardInterrupt:
        print("\n\n[INFO] Bridge dihentikan oleh pengguna.")


if __name__ == "__main__":
    main()

