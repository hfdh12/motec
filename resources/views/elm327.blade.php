@extends('layouts.app')

@section('content')
<!-- VEHICLE SELECTOR & HEADER -->
<div class="vehicle-selector-bar">
  <div>
    <h2 style="font-size: 20px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
      <span>🔌</span> KONEKTOR ELM327 OBD-II & DIAGNOSTIK ECU MOTOR
    </h2>
    <div style="font-size: 13px; color: var(--text-muted);">
      Hubungkan scanner ELM327 Bluetooth/USB/WiFi ke ECU sepeda motor Anda melalui browser secara langsung
    </div>
  </div>

  <div style="display: flex; gap: 10px; align-items: center;">
    <label for="elmVehicleSelect" style="font-size: 12px; font-weight: 700; color: var(--text-muted);">MOTOR:</label>
    <select id="elmVehicleSelect" class="select-control" style="min-width: 220px;" onchange="window.location.href='/elm327/' + this.value">
      @foreach ($vehicles as $v)
      <option value="{{ $v->id }}" {{ $v->id == $vehicle_id ? 'selected' : '' }}>
        {{ $v->brand }} {{ $v->model }} ({{ $v->plate_number }})
      </option>
      @endforeach
    </select>
    <a href="{{ route('live_hud.show', $vehicle_id) }}" class="btn btn-outline btn-sm">
      <span>⚡</span> Buka Live HUD
    </a>
  </div>
</div>

<!-- HARDWARE STATUS BANNER -->
<div class="glass-card" style="margin-bottom: 24px;">
  <div class="glass-card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
    <div style="display: flex; align-items: center; gap: 18px;">
      <div id="adapterStatusBadge" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; background: rgba(255, 42, 85, 0.15); border: 2px solid var(--red-crit); box-shadow: 0 0 15px rgba(255, 42, 85, 0.2);">
        🔌
      </div>
      <div>
        <div style="font-size: 11px; font-weight: 800; color: var(--text-muted); letter-spacing: 1.5px; text-transform: uppercase;">
          STATUS HARDWARE SCANNER
        </div>
        <h3 id="adapterStatusText" style="font-size: 18px; font-weight: 800; color: var(--red-crit); margin: 2px 0;">
          ELM327 Tidak Terhubung
        </h3>
        <p id="adapterSubText" style="font-size: 12px; color: var(--text-muted);">
          Pilih metode koneksi di bawah (Web Serial USB/Bluetooth, WiFi, atau Simulasi)
        </p>
      </div>
    </div>

    <!-- QUICK METRICS PILLS -->
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
      <div style="background: var(--bg-surface-elevated); padding: 10px 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); text-align: center; min-width: 100px;">
        <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">VOLTASE AKI</div>
        <div id="quickBattery" style="font-size: 18px; font-weight: 800; color: var(--cyan-glow); font-family: var(--font-mono);">-- V</div>
      </div>
      <div style="background: var(--bg-surface-elevated); padding: 10px 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); text-align: center; min-width: 100px;">
        <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">PROTOKOL ECU</div>
        <div id="quickProtocol" style="font-size: 14px; font-weight: 700; color: #fff; margin-top: 2px;">--</div>
      </div>
      <div style="background: var(--bg-surface-elevated); padding: 10px 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); text-align: center; min-width: 100px;">
        <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">STATUS ECU</div>
        <div id="quickEcuStatus" style="font-size: 14px; font-weight: 700; color: var(--amber-warn); margin-top: 2px;">Offline</div>
      </div>
    </div>
  </div>
</div>

<!-- MAIN GRID: CONNECTION CONTROLS & LIVE MONITOR -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">

  <!-- LEFT: CONNECTION PANEL -->
  <div class="glass-card">
    <div class="glass-card-header">
      <div class="glass-card-title">
        <span>⚙️</span> KONFIGURASI KONEKSI ELM327
      </div>
      <span class="badge badge-ok" id="activeMethodBadge">Web Serial API</span>
    </div>

    <div class="glass-card-body">
      <!-- CONNECTION METHOD SELECTOR TABS -->
      <div style="display: flex; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 12px; overflow-x: auto;">
        <button class="btn btn-primary btn-sm" id="tabBtnSerial" onclick="selectMethod('serial')">
          <span>🔌</span> USB / Bluetooth Serial
        </button>
        <button class="btn btn-outline btn-sm" id="tabBtnBle" onclick="selectMethod('ble')">
          <span>📶</span> Bluetooth BLE
        </button>
        <button class="btn btn-outline btn-sm" id="tabBtnWifi" onclick="selectMethod('wifi')">
          <span>🌐</span> WiFi (TCP/IP)
        </button>
        <button class="btn btn-outline btn-sm" id="tabBtnDemo" onclick="selectMethod('demo')">
          <span>🧪</span> Mode Demo
        </button>
      </div>

      <!-- TAB 1: WEB SERIAL (RECOMMENDED FOR CHROME/EDGE & BT COM PORT) -->
      <div id="methodSerial" class="method-panel">
        <div style="background: rgba(0, 240, 255, 0.05); border: 1px solid var(--border-glow); padding: 12px; border-radius: var(--radius-md); margin-bottom: 16px; font-size: 12px; line-height: 1.6;">
          <strong>💡 Direkomendasikan:</strong> Bekerja langsung di browser Chrome/Edge untuk adapter ELM327 USB (kabel FTDI/CH340) maupun adapter ELM327 Bluetooth (yang terpasang sebagai virtual COM port di Windows/Mac/Linux).
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
          <div>
            <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px;">BAUD RATE:</label>
            <select id="selBaudRate" class="select-control" style="width: 100%;">
              <option value="38400" selected>38400 baud (Standar ELM327 v1.5)</option>
              <option value="115200">115200 baud (ELM327 High Speed)</option>
              <option value="9600">9600 baud (Legacy)</option>
              <option value="500000">500000 baud (OBDLink SX / EX)</option>
            </select>
          </div>
          <div>
            <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px;">PROTOKOL OBD2:</label>
            <select id="selProtocol" class="select-control" style="width: 100%;">
              <option value="ATSP0" selected>Otomatis (ATSP0)</option>
              <option value="ATSP6">ISO 15765-4 CAN (11 bit, 500k) - Euro 5</option>
              <option value="ATSP7">ISO 15765-4 CAN (29 bit, 500k)</option>
              <option value="ATSP5">ISO 14230-4 KWP (Fast Init) - K-Line</option>
              <option value="ATSP3">ISO 9141-2 (5 Baud Init)</option>
            </select>
          </div>
        </div>

        <div style="display: flex; gap: 10px;">
          <button id="btnConnectSerial" class="btn btn-primary" style="flex: 1;" onclick="connectSerial()">
            <span>🔌</span> Hubungkan Port Serial ELM327
          </button>
          <button id="btnDisconnectSerial" class="btn btn-danger" style="display: none;" onclick="disconnectSerial()">
            <span>✖</span> Putuskan
          </button>
        </div>
      </div>

      <!-- TAB 2: BLE (WEB BLUETOOTH) -->
      <div id="methodBle" class="method-panel" style="display: none;">
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px; line-height: 1.6;">
          Pindai dan pasangkan scanner Bluetooth Low Energy (BLE 4.0 / OBDLink CX / Veepeak BLE / Vgate iCar Pro BLE).
        </div>
        <button id="btnConnectBle" class="btn btn-primary" style="width: 100%;" onclick="connectBle()">
          <span>📶</span> Pindai Perangkat Bluetooth OBD2
        </button>
      </div>

      <!-- TAB 3: WIFI (TCP/IP) -->
      <div id="methodWifi" class="method-panel" style="display: none;">
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px;">
          Koneksi ke adapter ELM327 WiFi (biasanya IP default <code>192.168.0.10</code> port <code>35000</code>).
        </div>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 14px;">
          <input type="text" id="wifiIp" class="input-control" value="192.168.0.10" placeholder="IP ELM327">
          <input type="number" id="wifiPort" class="input-control" value="35000" placeholder="Port">
        </div>
        <button class="btn btn-primary" style="width: 100%;" onclick="testWifiConnection()">
          <span>🌐</span> Hubungkan & Tes WiFi ELM327
        </button>
      </div>

      <!-- TAB 4: DEMO SIMULATION -->
      <div id="methodDemo" class="method-panel" style="display: none;">
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px; line-height: 1.6;">
          Uji coba seluruh alur penguraian hex OBD-II (RPM, Speed, ECT, TPS, DTC) dengan emulator virtual ELM327 tanpa memerlukan kabel fisik saat ini.
        </div>
        <button id="btnToggleDemo" class="btn btn-primary" style="width: 100%;" onclick="toggleDemoMode()">
          <span>🧪</span> Mulai Aliran Simulasi OBD-II
        </button>
      </div>

      <!-- AUTO-SYNC & POLLING SETTINGS -->
      <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-subtle);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
          <label style="font-size: 13px; font-weight: 700; color: #fff; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" id="chkAutoSync" checked style="accent-color: var(--cyan-glow); width: 16px; height: 16px;">
            <span>Sinkronkan ke MOTEC Real-Time (Live HUD & Cockpit)</span>
          </label>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between;">
          <span style="font-size: 12px; color: var(--text-muted);">Frekuensi Polling OBD:</span>
          <select id="selPollRate" class="select-control" style="font-size: 12px; padding: 4px 8px;">
            <option value="200">5 Hz (Cepat - 200ms)</option>
            <option value="500" selected>2 Hz (Stabil - 500ms)</option>
            <option value="1000">1 Hz (Santai - 1000ms)</option>
          </select>
        </div>
      </div>

      <!-- HANDSHAKE PROGRESS VISUALIZER -->
      <div style="margin-top: 20px;">
        <div style="font-size: 11px; font-weight: 800; color: var(--text-muted); letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 10px;">
          ALUR HANDSHAKE PROTOKOL ELM327
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <div class="handshake-step" id="stepPort">
            <span class="step-dot"></span> 1. Pembukaan Port Serial / Komunikasi
          </div>
          <div class="handshake-step" id="stepReset">
            <span class="step-dot"></span> 2. Reset Adapter (ATZ / ATE0 / ATL0)
          </div>
          <div class="handshake-step" id="stepProtocol">
            <span class="step-dot"></span> 3. Negosiasi Protokol ECU (ATSP / ATDP)
          </div>
          <div class="handshake-step" id="stepBattery">
            <span class="step-dot"></span> 4. Baca Voltase Sistem Aki (ATRV)
          </div>
          <div class="handshake-step" id="stepPids">
            <span class="step-dot"></span> 5. Sinkronisasi Data Sensor ECU (Mode 01)
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- RIGHT: LIVE OBD TELEMETRY GAUGES -->
  <div class="glass-card">
    <div class="glass-card-header">
      <div class="glass-card-title">
        <span>📊</span> MONITOR LIVE DATA SENSOR ECU (MODE 01)
      </div>
      <div style="font-size: 12px; color: var(--cyan-glow); font-family: var(--font-mono);" id="pollStatusIndicator">
        Idle
      </div>
    </div>

    <div class="glass-card-body">
      <!-- 4 KEY PRIMARY METRICS -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 14px; margin-bottom: 20px;">
        <!-- RPM -->
        <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); text-align: center;">
          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted);">RPM MESIN [010C]</div>
          <div id="gaugeRpm" style="font-size: 26px; font-weight: 800; color: #fff; font-family: var(--font-mono); margin: 4px 0;">0</div>
          <div style="font-size: 10px; color: var(--text-dim);">PUTARAN / MENIT</div>
        </div>

        <!-- SPEED -->
        <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); text-align: center;">
          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted);">KECEPATAN [010D]</div>
          <div id="gaugeSpeed" style="font-size: 26px; font-weight: 800; color: var(--cyan-glow); font-family: var(--font-mono); margin: 4px 0;">0</div>
          <div style="font-size: 10px; color: var(--text-dim);">KM / JAM</div>
        </div>

        <!-- COOLANT / ECT -->
        <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); text-align: center;">
          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted);">SUHU MESIN / ECT [0105]</div>
          <div id="gaugeTemp" style="font-size: 26px; font-weight: 800; color: var(--green-ok); font-family: var(--font-mono); margin: 4px 0;">--</div>
          <div style="font-size: 10px; color: var(--text-dim);">DERAJAT CELSIUS (°C)</div>
        </div>

        <!-- THROTTLE / TPS -->
        <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); text-align: center;">
          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted);">BUKAAN GAS / TPS [0111]</div>
          <div id="gaugeTps" style="font-size: 26px; font-weight: 800; color: var(--amber-warn); font-family: var(--font-mono); margin: 4px 0;">0%</div>
          <div style="font-size: 10px; color: var(--text-dim);">PERSENTASE (%)</div>
        </div>
      </div>

      <!-- SECONDARY SENSORS TABLE -->
      <div style="background: var(--bg-surface-elevated); border-radius: var(--radius-md); border: 1px solid var(--border-subtle); padding: 14px; margin-bottom: 20px;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 10px; display: flex; justify-content: space-between;">
          <span>SENSOR TAMBAHAN</span>
          <span>OBD PID</span>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
          <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 6px;">
            <span style="color: var(--text-muted);">Voltase Aki Langsung (Pin 16 OBD2)</span>
            <span id="subValBatt" style="font-family: var(--font-mono); font-weight: 700; color: var(--cyan-glow);">-- V</span>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 6px;">
            <span style="color: var(--text-muted);">Tekanan Manifold / MAP [010B]</span>
            <span id="subValMap" style="font-family: var(--font-mono); font-weight: 700; color: #fff;">-- kPa</span>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 6px;">
            <span style="color: var(--text-muted);">Suhu Udara Masuk / IAT [010F]</span>
            <span id="subValIat" style="font-family: var(--font-mono); font-weight: 700; color: #fff;">-- °C</span>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 6px;">
            <span style="color: var(--text-muted);">Sensor Oksigen O2 Lambda [0114]</span>
            <span id="subValO2" style="font-family: var(--font-mono); font-weight: 700; color: #fff;">-- V</span>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="color: var(--text-muted);">Kapasitas Bensin [012F]</span>
            <span id="subValFuel" style="font-family: var(--font-mono); font-weight: 700; color: #fff;">-- %</span>
          </div>
        </div>
      </div>

      <!-- DTC ACTION BUTTONS -->
      <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button class="btn btn-outline" style="flex: 1;" onclick="readDtcFromElm()">
          <span>🔍</span> Scan DTC ECU (Mode 03)
        </button>
        <button class="btn btn-danger" onclick="clearDtcViaElm()">
          <span>🧹</span> Reset MIL / Clear DTC (Mode 04)
        </button>
      </div>

    </div>
  </div>

</div>

<!-- DTC RESULTS MODAL/CARD -->
<div class="glass-card" style="margin-bottom: 24px;">
  <div class="glass-card-header">
    <div class="glass-card-title">
      <span>⚠️</span> HASIL PEMINDAIAN DIAGNOSTIC TROUBLE CODES (DTC) DARI ECU
    </div>
    <span class="badge" id="dtcCountBadge" style="background: rgba(0, 230, 118, 0.2); color: #00e676;">
      0 Kode Terdeteksi
    </span>
  </div>

  <div class="glass-card-body" id="dtcListContainer">
    @if ($activeDtcs->isEmpty())
    <div style="text-align: center; padding: 24px 0; color: var(--text-muted);">
      <div style="font-size: 36px; margin-bottom: 10px;">✅</div>
      <div style="font-size: 15px; font-weight: 700; color: #00e676;">ECU Bersih dari Kode Gangguan</div>
      <div style="font-size: 13px; margin-top: 4px;">Tekan tombol "Scan DTC ECU (Mode 03)" di atas untuk memeriksa memori diagnostik motor secara langsung via ELM327.</div>
    </div>
    @else
    <div style="display: flex; flex-direction: column; gap: 12px;">
      @foreach ($activeDtcs as $f)
      <div style="background: var(--bg-surface-elevated); border-left: 4px solid var(--red-crit); padding: 14px 18px; border-radius: var(--radius-sm); display: flex; justify-content: space-between; align-items: center;">
        <div>
          <div style="font-family: var(--font-mono); font-size: 16px; font-weight: 800; color: var(--red-crit);">
            {{ $f->dtc_code }}
          </div>
          <div style="font-size: 14px; font-weight: 700; color: #fff; margin-top: 2px;">
            {{ $f->dtc->title_id ?? 'Gangguan Sensor Mesin' }}
          </div>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
            {{ $f->dtc->description_id ?? '' }}
          </div>
        </div>
        <span class="badge badge-crit">AKTIF</span>
      </div>
      @endforeach
    </div>
    @endif
  </div>
</div>

<!-- INTERACTIVE ELM327 AT CONSOLE / TERMINAL -->
<div class="glass-card" style="margin-bottom: 24px;">
  <div class="glass-card-header">
    <div class="glass-card-title">
      <span>💻</span> TERMINAL & KONSOL PERINTAH RAW ELM327 AT COMMANDS
    </div>
    <div style="display: flex; gap: 8px;">
      <button class="btn btn-outline btn-sm" onclick="clearTerminal()">
        <span>🧹</span> Bersihkan Layar
      </button>
    </div>
  </div>

  <div class="glass-card-body">
    <!-- QUICK MACRO CHIPS -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px;">
      <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); align-self: center;">QUICK MACROS:</span>
      <button class="btn btn-outline btn-sm" style="font-family: var(--font-mono); font-size: 11px;" onclick="sendRawCommand('ATZ')">ATZ (Reset)</button>
      <button class="btn btn-outline btn-sm" style="font-family: var(--font-mono); font-size: 11px;" onclick="sendRawCommand('ATI')">ATI (Version)</button>
      <button class="btn btn-outline btn-sm" style="font-family: var(--font-mono); font-size: 11px;" onclick="sendRawCommand('ATRV')">ATRV (Voltase Aki)</button>
      <button class="btn btn-outline btn-sm" style="font-family: var(--font-mono); font-size: 11px;" onclick="sendRawCommand('ATDP')">ATDP (Protokol)</button>
      <button class="btn btn-outline btn-sm" style="font-family: var(--font-mono); font-size: 11px;" onclick="sendRawCommand('0100')">0100 (Supported PIDs)</button>
      <button class="btn btn-outline btn-sm" style="font-family: var(--font-mono); font-size: 11px;" onclick="sendRawCommand('010C')">010C (RPM)</button>
      <button class="btn btn-outline btn-sm" style="font-family: var(--font-mono); font-size: 11px;" onclick="sendRawCommand('0105')">0105 (ECT)</button>
      <button class="btn btn-outline btn-sm" style="font-family: var(--font-mono); font-size: 11px;" onclick="sendRawCommand('03')">03 (Read DTC)</button>
    </div>

    <!-- TERMINAL SCREEN -->
    <div id="terminalScreen" style="background: #060910; border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 14px; height: 200px; overflow-y: auto; font-family: var(--font-mono); font-size: 12px; color: #a5f3fc; line-height: 1.5; margin-bottom: 12px; white-space: pre-wrap;">[SISTEM] MOTEC ELM327 OBD-II Terminal Siap.
Silakan klik "Hubungkan Port Serial ELM327" atau gunakan tombol Quick Macro di atas.</div>

    <!-- COMMAND INPUT BAR -->
    <form onsubmit="handleTerminalSubmit(event)" style="display: flex; gap: 10px;">
      <input type="text" id="termInput" class="input-control" placeholder="Ketik perintah AT atau PID OBD2 (misal: ATRV, ATZ, 010C)..." style="font-family: var(--font-mono); font-size: 13px;">
      <button type="submit" class="btn btn-primary" style="min-width: 100px;">
        <span>🚀</span> Kirim
      </button>
    </form>
  </div>
</div>

<!-- HARDWARE PINOUT & MOTORCYCLE ADAPTER GUIDE -->
<div class="glass-card">
  <div class="glass-card-header">
    <div class="glass-card-title">
      <span>📖</span> PANDUAN KABEL ADAPTER SOKET DIAGNOSTIK MOTOR KE OBD-II 16-PIN
    </div>
  </div>

  <div class="glass-card-body">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; font-size: 13px; line-height: 1.6;">
      
      <!-- EURO 5 STANDARD -->
      <div style="background: var(--bg-surface-elevated); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
        <div style="font-weight: 800; color: #fff; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
          <span>🔴</span> Standar Euro 5 (Soket Merah 6-Pin)
        </div>
        <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 10px;">
          Motor keluaran 2021 ke atas (Yamaha NMAX Connected, Aerox VVA, Honda Vario 160, PCX 160, ZX-25R, dll).
        </p>
        <ul style="padding-left: 18px; color: var(--text-muted); font-size: 12px;">
          <li><strong>Pin 1:</strong> CAN High (Hubungkan ke Pin 6 OBD-II)</li>
          <li><strong>Pin 2:</strong> CAN Low (Hubungkan ke Pin 14 OBD-II)</li>
          <li><strong>Pin 3:</strong> K-Line (Hubungkan ke Pin 7 OBD-II)</li>
          <li><strong>Pin 4:</strong> Ground / Massa (Pin 4 & 5 OBD-II)</li>
          <li><strong>Pin 5:</strong> +12V Baterai (Hubungkan ke Pin 16 OBD-II)</li>
        </ul>
      </div>

      <!-- HONDA 4-PIN -->
      <div style="background: var(--bg-surface-elevated); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
        <div style="font-weight: 800; color: #fff; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
          <span>🔴</span> Honda DLC 4-Pin (Soket Merah)
        </div>
        <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 10px;">
          Motor Honda injeksi pre-Euro 5 (Beat ESP, Vario 125/150, Scoopy, CBR 150R, CB150R, dll).
        </p>
        <ul style="padding-left: 18px; color: var(--text-muted); font-size: 12px;">
          <li>Kabel Oranye/Hitam: K-Line (Pin 7 OBD-II)</li>
          <li>Kabel Hijau: Ground (Pin 4 & 5 OBD-II)</li>
          <li>Kabel Cokelat/Biru: SCS Service Check</li>
          <li>Kabel Merah/Kuning: +12V DC (Pin 16 OBD-II)</li>
        </ul>
      </div>

      <!-- YAMAHA 4-PIN -->
      <div style="background: var(--bg-surface-elevated); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
        <div style="font-weight: 800; color: #fff; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
          <span>⚪</span> Yamaha 4-Pin (Soket Putih)
        </div>
        <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 10px;">
          Motor Yamaha injeksi (NMAX Gen 1, R15 V3, MT-15, MX King, XSR 155 pre-2021).
        </p>
        <ul style="padding-left: 18px; color: var(--text-muted); font-size: 12px;">
          <li>Kabel Biru: K-Line / Yamaha Comm (Pin 7 OBD-II)</li>
          <li>Kabel Hitam: Ground / Massa (Pin 4 & 5 OBD-II)</li>
          <li>Kabel Merah: +12V Baterai (Pin 16 OBD-II)</li>
        </ul>
      </div>

    </div>
  </div>
</div>

<style>
.handshake-step {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-muted);
  transition: all 0.2s ease;
}
.handshake-step .step-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--text-dim);
}
.handshake-step.done {
  color: #00e676;
}
.handshake-step.done .step-dot {
  background: #00e676;
  box-shadow: 0 0 8px #00e676;
}
.handshake-step.active {
  color: var(--cyan-glow);
}
.handshake-step.active .step-dot {
  background: var(--cyan-glow);
  box-shadow: 0 0 8px var(--cyan-glow);
  animation: pulse 1s infinite alternate;
}
@keyframes pulse {
  from { opacity: 0.4; }
  to { opacity: 1; }
}
</style>

<script>
// ==============================================================================
// MOTEC ELM327 OBD-II WEB SERIAL & TELEMETRY INGESTION ENGINE
// ==============================================================================

const VEHICLE_ID = {{ $vehicle_id }};
let serialPort = null;
let serialReader = null;
let serialWriter = null;
let isConnected = false;
let pollingInterval = null;
let demoInterval = null;
let isDemoRunning = false;
let responseBuffer = "";
let pendingCommandResolver = null;

// Telemetry State Cache
let currentTelemetry = {
  vehicle_id: VEHICLE_ID,
  engine_rpm: 0,
  speed_kmh: 0,
  engine_temp_c: 85,
  battery_voltage_v: 12.6,
  tps_throttle_percent: 0,
  map_sensor_kpa: 101.3,
  ambient_temp_c: 30,
  o2_sensor_v: 0.45,
  fuel_level_percent: 75,
  odometer_km: {{ $vehicle->current_odometer_km ?? 1000 }}
};

// Method selection
function selectMethod(method) {
  ['serial', 'ble', 'wifi', 'demo'].forEach(m => {
    document.getElementById('method' + capitalize(m)).style.display = (m === method) ? 'block' : 'none';
    const btn = document.getElementById('tabBtn' + capitalize(m));
    if (btn) {
      btn.className = (m === method) ? 'btn btn-primary btn-sm' : 'btn btn-outline btn-sm';
    }
  });
  document.getElementById('activeMethodBadge').innerText = {
    serial: 'Web Serial (USB/BT)',
    ble: 'Bluetooth BLE',
    wifi: 'WiFi TCP/IP',
    demo: 'Simulasi Virtual'
  }[method];
}

function capitalize(s) {
  return s.charAt(0).toUpperCase() + s.slice(1);
}

// ------------------------------------------------------------------------------
// TERMINAL LOGGING
// ------------------------------------------------------------------------------
function logTerm(msg, type = "info") {
  const term = document.getElementById('terminalScreen');
  const now = new Date().toTimeString().split(' ')[0];
  let prefix = `[${now}] `;
  if (type === "tx") prefix += "TX > ";
  else if (type === "rx") prefix += "RX < ";
  else prefix += "[INFO] ";

  term.textContent += `\n${prefix}${msg}`;
  term.scrollTop = term.scrollHeight;
}

function clearTerminal() {
  document.getElementById('terminalScreen').textContent = "[SISTEM] Terminal dibersihkan.";
}

function setHandshakeStep(stepId, state) {
  const el = document.getElementById(stepId);
  if (!el) return;
  el.classList.remove('done', 'active');
  if (state === 'done') el.classList.add('done');
  if (state === 'active') el.classList.add('active');
}

// ------------------------------------------------------------------------------
// WEB SERIAL API (CHROME / EDGE NATIVE)
// ------------------------------------------------------------------------------
async function connectSerial() {
  if (!('serial' in navigator)) {
    alert("Browser Anda belum mendukung Web Serial API. Silakan gunakan Google Chrome, Microsoft Edge, atau Opera terbaru.");
    return;
  }

  const baudRate = parseInt(document.getElementById('selBaudRate').value) || 38400;

  try {
    logTerm(`Membuka pemilih port serial...`);
    setHandshakeStep('stepPort', 'active');
    serialPort = await navigator.serial.requestPort();
    await serialPort.open({ baudRate: baudRate });

    isConnected = true;
    setHandshakeStep('stepPort', 'done');
    logTerm(`Port serial berhasil dibuka pada baud rate ${baudRate} bps.`);

    document.getElementById('btnConnectSerial').style.display = 'none';
    document.getElementById('btnDisconnectSerial').style.display = 'inline-flex';
    updateAdapterStatus(true, "ELM327 Terhubung (Serial)", "Sedang inisialisasi handshake ECU...");

    // Start background reading stream
    readSerialStream();

    // Begin Handshake sequence
    await runHandshake();

    // Start continuous PID polling loop
    startPollingLoop();

  } catch (err) {
    console.error(err);
    logTerm(`Gagal menghubungkan serial port: ${err.message}`, "error");
    setHandshakeStep('stepPort', '');
    updateAdapterStatus(false, "Koneksi Gagal", err.message);
  }
}

async function readSerialStream() {
  const textDecoder = new TextDecoderStream();
  const readableStreamClosed = serialPort.readable.pipeTo(textDecoder.writable);
  serialReader = textDecoder.readable.getReader();

  try {
    while (true) {
      const { value, done } = await serialReader.read();
      if (done) {
        break;
      }
      if (value) {
        handleIncomingData(value);
      }
    }
  } catch (err) {
    console.error("Serial read error:", err);
  }
}

function handleIncomingData(chunk) {
  responseBuffer += chunk;
  
  // ELM327 terminates responses with '>'
  if (responseBuffer.includes('>')) {
    const parts = responseBuffer.split('>');
    const completeResponse = parts[0].trim();
    responseBuffer = parts.slice(1).join('>');

    if (completeResponse.length > 0) {
      logTerm(completeResponse, "rx");
      if (pendingCommandResolver) {
        pendingCommandResolver(completeResponse);
        pendingCommandResolver = null;
      }
    }
  }
}

async function sendCommand(cmd, timeoutMs = 2500) {
  if (!serialPort || !serialPort.writable) {
    throw new Error("Port serial belum terbuka.");
  }

  logTerm(cmd, "tx");
  responseBuffer = "";

  const textEncoder = new TextEncoderStream();
  const writableStreamClosed = textEncoder.readable.pipeTo(serialPort.writable);
  const writer = textEncoder.writable.getWriter();
  await writer.write(cmd + "\r");
  writer.releaseLock();

  return new Promise((resolve, reject) => {
    pendingCommandResolver = resolve;
    setTimeout(() => {
      if (pendingCommandResolver) {
        pendingCommandResolver = null;
        resolve("TIMEOUT");
      }
    }, timeoutMs);
  });
}

// ------------------------------------------------------------------------------
// ELM327 HANDSHAKE SEQUENCE
// ------------------------------------------------------------------------------
async function runHandshake() {
  logTerm("Memulai prosedur inisialisasi ELM327...");
  
  // Step 2: Reset
  setHandshakeStep('stepReset', 'active');
  await sendCommand("ATZ", 1500); // Reset
  await new Promise(r => setTimeout(r, 600));
  await sendCommand("ATE0", 800); // Echo off
  await sendCommand("ATL0", 800); // Linefeeds off
  await sendCommand("ATS0", 800); // Spaces off
  setHandshakeStep('stepReset', 'done');

  // Step 3: Protocol
  setHandshakeStep('stepProtocol', 'active');
  const chosenProto = document.getElementById('selProtocol').value;
  await sendCommand(chosenProto, 1000);
  const protoResp = await sendCommand("ATDP", 1000);
  document.getElementById('quickProtocol').innerText = protoResp.replace(/OK|SEARCHING|\r|\n/g, '').trim() || 'ISO 15765-4';
  setHandshakeStep('stepProtocol', 'done');

  // Step 4: Battery Voltage
  setHandshakeStep('stepBattery', 'active');
  const voltResp = await sendCommand("ATRV", 1000);
  decodeAndApplyPid('ATRV', voltResp);
  setHandshakeStep('stepBattery', 'done');

  // Step 5: Test ECU PID 0100
  setHandshakeStep('stepPids', 'active');
  const ecuCheck = await sendCommand("0100", 2500);
  if (ecuCheck.includes("UNABLE") || ecuCheck.includes("NO DATA") || ecuCheck.includes("TIMEOUT")) {
    document.getElementById('quickEcuStatus').innerText = "Kontak OFF / Menunggu";
    document.getElementById('quickEcuStatus').style.color = "var(--amber-warn)";
    logTerm("ECU belum merespon. Pastikan kontak sepeda motor dalam posisi ON.", "warn");
  } else {
    document.getElementById('quickEcuStatus').innerText = "ECU Terhubung (OK)";
    document.getElementById('quickEcuStatus').style.color = "#00e676";
    setHandshakeStep('stepPids', 'done');
    logTerm("Komunikasi dengan ECU motor berhasil terjalin!", "info");
    registerDeviceWithBackend();
  }
}

async function disconnectSerial() {
  stopPollingLoop();
  if (serialReader) {
    await serialReader.cancel();
  }
  if (serialPort) {
    await serialPort.close();
  }
  isConnected = false;
  document.getElementById('btnConnectSerial').style.display = 'inline-flex';
  document.getElementById('btnDisconnectSerial').style.display = 'none';
  updateAdapterStatus(false, "ELM327 Terputus", "Koneksi port serial telah dihentikan.");
  logTerm("Port serial ditutup.");
}

// ------------------------------------------------------------------------------
// CONTINUOUS OBD-II PID POLLING LOOP
// ------------------------------------------------------------------------------
function startPollingLoop() {
  const rateMs = parseInt(document.getElementById('selPollRate').value) || 500;
  if (pollingInterval) clearInterval(pollingInterval);

  document.getElementById('pollStatusIndicator').innerText = "Polling Aktif (" + rateMs + "ms)";

  pollingInterval = setInterval(async () => {
    if (!isConnected) return;

    try {
      // Poll RPM (010C)
      const rpmResp = await sendCommand("010C", 600);
      decodeAndApplyPid("410C", rpmResp);

      // Poll Speed (010D)
      const spdResp = await sendCommand("010D", 600);
      decodeAndApplyPid("410D", spdResp);

      // Poll Coolant ECT (0105)
      const ectResp = await sendCommand("0105", 600);
      decodeAndApplyPid("4105", ectResp);

      // Poll Throttle TPS (0111)
      const tpsResp = await sendCommand("0111", 600);
      decodeAndApplyPid("4111", tpsResp);

      // Ingest to MOTEC backend
      if (document.getElementById('chkAutoSync').checked) {
        syncTelemetryToMotec();
      }
    } catch (e) {
      console.warn("Polling cycle skip:", e);
    }
  }, rateMs);
}

function stopPollingLoop() {
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }
  document.getElementById('pollStatusIndicator').innerText = "Idle";
}

// ------------------------------------------------------------------------------
// DECODE OBD PIDS LOCALLY & UPDATE UI
// ------------------------------------------------------------------------------
function decodeAndApplyPid(pidKey, raw) {
  const clean = raw.replace(/[^0-9A-Fa-f]/g, '').toUpperCase();

  // 1. ATRV (Battery Voltage)
  if (pidKey === 'ATRV' || raw.includes('V')) {
    const match = raw.match(/([0-9]+\.?[0-9]*)/);
    if (match) {
      const v = parseFloat(match[1]);
      currentTelemetry.battery_voltage_v = v;
      document.getElementById('quickBattery').innerText = v.toFixed(1) + " V";
      document.getElementById('subValBatt').innerText = v.toFixed(1) + " V";
    }
    return;
  }

  // 2. RPM (010C -> 41 0C A B) Formula: ((A*256)+B)/4
  if (clean.includes("410C")) {
    const idx = clean.indexOf("410C") + 4;
    const hex = clean.substring(idx, idx + 4);
    if (hex.length >= 4) {
      const a = parseInt(hex.substring(0, 2), 16);
      const b = parseInt(hex.substring(2, 4), 16);
      const rpm = Math.round(((a * 256) + b) / 4);
      currentTelemetry.engine_rpm = rpm;
      document.getElementById('gaugeRpm').innerText = rpm.toLocaleString();
    }
  }

  // 3. Speed (010D -> 41 0D A) Formula: A km/h
  if (clean.includes("410D")) {
    const idx = clean.indexOf("410D") + 4;
    const hex = clean.substring(idx, idx + 2);
    if (hex.length >= 2) {
      const spd = parseInt(hex, 16);
      currentTelemetry.speed_kmh = spd;
      document.getElementById('gaugeSpeed').innerText = spd;
    }
  }

  // 4. Coolant Temp (0105 -> 41 05 A) Formula: A - 40 deg C
  if (clean.includes("4105")) {
    const idx = clean.indexOf("4105") + 4;
    const hex = clean.substring(idx, idx + 2);
    if (hex.length >= 2) {
      const temp = parseInt(hex, 16) - 40;
      currentTelemetry.engine_temp_c = temp;
      document.getElementById('gaugeTemp').innerText = temp + " °C";
      document.getElementById('gaugeTemp').style.color = (temp > 105) ? "var(--red-crit)" : "#00e676";
    }
  }

  // 5. TPS (0111 -> 41 11 A) Formula: (A * 100) / 255
  if (clean.includes("4111")) {
    const idx = clean.indexOf("4111") + 4;
    const hex = clean.substring(idx, idx + 2);
    if (hex.length >= 2) {
      const tps = Math.round((parseInt(hex, 16) * 100) / 255);
      currentTelemetry.tps_throttle_percent = tps;
      document.getElementById('gaugeTps').innerText = tps + "%";
    }
  }
}

// ------------------------------------------------------------------------------
// DTC CODES SCANNING & CLEARING
// ------------------------------------------------------------------------------
async function readDtcFromElm() {
  logTerm("Mengirim perintah Mode 03 (Request Trouble Codes) ke ECU...");
  let dtcResp = "";

  if (isDemoRunning) {
    dtcResp = "43 01 15 05 62"; // Sample DTCs for demo
  } else if (isConnected) {
    dtcResp = await sendCommand("03", 3000);
  } else {
    alert("Silakan hubungkan adapter ELM327 terlebih dahulu, atau nyalakan Mode Demo.");
    return;
  }

  logTerm(`Respons DTC ECU: ${dtcResp}`);

  // Send to backend decoding endpoint
  try {
    const res = await fetch('/api/elm327/decode', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        responses: { '03': dtcResp }
      })
    });

    const data = await res.json();
    const codes = data.decoded?.dtc_codes || [];
    renderDtcResults(codes);

    // Ingest DTCs into MOTEC
    if (codes.length > 0) {
      await fetch('/api/telemetry/ingest', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
          vehicle_id: VEHICLE_ID,
          dtc_codes: codes
        })
      });
    }

  } catch (err) {
    console.error(err);
    logTerm("Gagal mengurai DTC: " + err.message, "error");
  }
}

function renderDtcResults(codes) {
  const container = document.getElementById('dtcListContainer');
  const countBadge = document.getElementById('dtcCountBadge');

  if (!codes || codes.length === 0) {
    countBadge.innerText = "0 Kode Terdeteksi";
    countBadge.style.background = "rgba(0, 230, 118, 0.2)";
    countBadge.style.color = "#00e676";
    container.innerHTML = `
      <div style="text-align: center; padding: 24px 0; color: var(--text-muted);">
        <div style="font-size: 36px; margin-bottom: 10px;">✅</div>
        <div style="font-size: 15px; font-weight: 700; color: #00e676;">ECU Bersih dari Kode Gangguan (Normal)</div>
        <div style="font-size: 13px; margin-top: 4px;">Tidak ada kode kesalahan aktif yang tersimpan di memori ECU.</div>
      </div>
    `;
    return;
  }

  countBadge.innerText = `${codes.length} Kode Aktif`;
  countBadge.style.background = "rgba(255, 42, 85, 0.25)";
  countBadge.style.color = "var(--red-crit)";

  let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';
  codes.forEach(c => {
    html += `
      <div style="background: var(--bg-surface-elevated); border-left: 4px solid var(--red-crit); padding: 14px 18px; border-radius: var(--radius-sm); display: flex; justify-content: space-between; align-items: center;">
        <div>
          <div style="font-family: var(--font-mono); font-size: 16px; font-weight: 800; color: var(--red-crit);">
            ${c}
          </div>
          <div style="font-size: 14px; font-weight: 700; color: #fff; margin-top: 2px;">
            Gangguan Sensor ECU Terdeteksi
          </div>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
            Kode malfungsi aktif terbaca langsung dari protokol OBD-II Mode 03.
          </div>
        </div>
        <span class="badge badge-crit">MIL AKTIF</span>
      </div>
    `;
  });
  html += '</div>';
  container.innerHTML = html;
}

async function clearDtcViaElm() {
  if (!confirm("Peringatan: Perintah Mode 04 akan menghapus kode kesalahan dari memori ECU motor dan mematikan lampu Check Engine (MIL). Lanjutkan?")) {
    return;
  }

  logTerm("Mengirim perintah Mode 04 (Clear DTCs / Reset ECU)...");
  if (isDemoRunning) {
    logTerm("Mode 04 berhasil diuji coba (Simulasi).");
  } else if (isConnected) {
    const clearResp = await sendCommand("04", 3000);
    logTerm(`Respons Clear DTC: ${clearResp}`);
  }

  // Clear in MOTEC backend
  try {
    const res = await fetch(`/api/dtc/clear/${VEHICLE_ID}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    });
    const d = await res.json();
    renderDtcResults([]);
    alert("Kode DTC berhasil dihapus dari ECU dan sistem MOTEC!");
  } catch (err) {
    console.error(err);
  }
}

// ------------------------------------------------------------------------------
// SYNC TELEMETRY TO MOTEC BACKEND
// ------------------------------------------------------------------------------
async function syncTelemetryToMotec() {
  try {
    await fetch('/api/telemetry/ingest', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(currentTelemetry)
    });
  } catch (e) {
    console.warn("Backend sync failed:", e);
  }
}

async function registerDeviceWithBackend() {
  try {
    await fetch(`/api/elm327/register/${VEHICLE_ID}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        device_uid: 'ELM327-OBD2-' + VEHICLE_ID,
        device_model: 'ELM327 Bluetooth/USB v1.5',
        firmware_version: 'v1.5 OBD-II'
      })
    });
    logTerm("Perangkat ELM327 berhasil didaftarkan ke profil kendaraan MOTEC.");
  } catch (e) {
    console.warn("Device registration skip:", e);
  }
}

// ------------------------------------------------------------------------------
// DEMO SIMULATION MODE
// ------------------------------------------------------------------------------
function toggleDemoMode() {
  const btn = document.getElementById('btnToggleDemo');
  if (isDemoRunning) {
    clearInterval(demoInterval);
    isDemoRunning = false;
    btn.innerHTML = '<span>🧪</span> Mulai Aliran Simulasi OBD-II';
    btn.className = 'btn btn-primary';
    updateAdapterStatus(false, "Simulasi Dihentikan", "ELM327 offline.");
    logTerm("Mode simulasi virtual dihentikan.");
  } else {
    isDemoRunning = true;
    btn.innerHTML = '<span>⏹️</span> Hentikan Aliran Simulasi';
    btn.className = 'btn btn-danger';
    updateAdapterStatus(true, "ELM327 Virtual (Demo Mode)", "Menghasilkan respons hex OBD-II real-time.");
    logTerm("Mode simulasi virtual ELM327 aktif.");

    // Handshake animation in demo
    setHandshakeStep('stepPort', 'done');
    setHandshakeStep('stepReset', 'done');
    setHandshakeStep('stepProtocol', 'done');
    setHandshakeStep('stepBattery', 'done');
    setHandshakeStep('stepPids', 'done');
    document.getElementById('quickBattery').innerText = "14.2 V";
    document.getElementById('quickProtocol').innerText = "ISO 15765-4 (CAN 11/500)";
    document.getElementById('quickEcuStatus').innerText = "ECU Normal (OK)";
    document.getElementById('quickEcuStatus').style.color = "#00e676";

    let t = 0;
    demoInterval = setInterval(() => {
      t += 0.1;
      const rpm = Math.round(1800 + Math.sin(t) * 1200 + Math.random() * 80);
      const spd = Math.round(Math.max(0, (rpm - 1500) * 0.035));
      const temp = Math.round(84 + Math.sin(t * 0.2) * 4);
      const tps = Math.round(Math.max(0, Math.sin(t) * 40));

      currentTelemetry.engine_rpm = rpm;
      currentTelemetry.speed_kmh = spd;
      currentTelemetry.engine_temp_c = temp;
      currentTelemetry.tps_throttle_percent = tps;
      currentTelemetry.battery_voltage_v = 14.1;

      document.getElementById('gaugeRpm').innerText = rpm.toLocaleString();
      document.getElementById('gaugeSpeed').innerText = spd;
      document.getElementById('gaugeTemp').innerText = temp + " °C";
      document.getElementById('gaugeTps').innerText = tps + "%";

      if (document.getElementById('chkAutoSync').checked) {
        syncTelemetryToMotec();
      }
    }, 400);
  }
}

// ------------------------------------------------------------------------------
// TERMINAL INPUT HANDLING
// ------------------------------------------------------------------------------
async function handleTerminalSubmit(e) {
  e.preventDefault();
  const input = document.getElementById('termInput');
  const cmd = input.value.trim();
  if (!cmd) return;
  input.value = "";
  await sendRawCommand(cmd);
}

async function sendRawCommand(cmd) {
  if (isDemoRunning) {
    logTerm(cmd, "tx");
    setTimeout(() => {
      let mock = "OK";
      if (cmd === "ATZ") mock = "ELM327 v1.5";
      else if (cmd === "ATI") mock = "ELM327 v1.5 OBDII";
      else if (cmd === "ATRV") mock = "14.2V";
      else if (cmd === "ATDP") mock = "ISO 15765-4 (CAN 11/500)";
      else if (cmd === "0100") mock = "41 00 BE 3F B8 11";
      else if (cmd === "010C") mock = "41 0C 1A F8"; // ~1726 RPM
      else if (cmd === "0105") mock = "41 05 7C"; // 84 C
      else if (cmd === "03") mock = "43 01 15 05 62";
      logTerm(mock, "rx");
      decodeAndApplyPid(cmd, mock);
    }, 150);
    return;
  }

  if (!isConnected) {
    alert("Adapter ELM327 belum terhubung. Silakan klik 'Hubungkan Port Serial' terlebih dahulu.");
    return;
  }

  try {
    const resp = await sendCommand(cmd);
    decodeAndApplyPid(cmd, resp);
  } catch (err) {
    logTerm("Error pengiriman: " + err.message, "error");
  }
}

// ------------------------------------------------------------------------------
// UI HELPERS
// ------------------------------------------------------------------------------
function updateAdapterStatus(connected, title, sub) {
  const badge = document.getElementById('adapterStatusBadge');
  const titleEl = document.getElementById('adapterStatusText');
  const subEl = document.getElementById('adapterSubText');

  if (connected) {
    badge.style.borderColor = "#00e676";
    badge.style.background = "rgba(0, 230, 118, 0.15)";
    badge.style.boxShadow = "0 0 15px rgba(0, 230, 118, 0.3)";
    badge.innerHTML = "✅";
    titleEl.style.color = "#00e676";
  } else {
    badge.style.borderColor = "var(--red-crit)";
    badge.style.background = "rgba(255, 42, 85, 0.15)";
    badge.style.boxShadow = "0 0 15px rgba(255, 42, 85, 0.2)";
    badge.innerHTML = "🔌";
    titleEl.style.color = "var(--red-crit)";
  }
  titleEl.innerText = title;
  subEl.innerText = sub;
}

// BLE Stub
async function connectBle() {
  if (!('bluetooth' in navigator)) {
    alert("Browser Anda belum mendukung Web Bluetooth API.");
    return;
  }
  try {
    logTerm("Membuka dialog pemindaian Bluetooth Low Energy...");
    const device = await navigator.bluetooth.requestDevice({
      acceptAllDevices: true,
      optionalServices: ['generic_access', '0000fff0-0000-1000-8000-00805f9b34fb']
    });
    logTerm(`Perangkat Bluetooth terpilih: ${device.name}`);
    alert(`Terhubung ke perangkat BLE: ${device.name}`);
  } catch (e) {
    logTerm("Pencarian BLE dibatalkan: " + e.message, "warn");
  }
}

// WiFi Tester
async function testWifiConnection() {
  const ip = document.getElementById('wifiIp').value;
  const port = document.getElementById('wifiPort').value;
  logTerm(`Menghubungi ELM327 WiFi di ${ip}:${port}...`);
  try {
    const res = await fetch('/api/elm327/wifi-proxy', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ host: ip, port: port, command: 'ATZ' })
    });
    const d = await res.json();
    if (d.status === 'success') {
      logTerm(`Respons WiFi ELM327: ${d.response}`);
      alert("Koneksi WiFi ELM327 Berhasil! Respons: " + d.response);
    } else {
      logTerm(`Koneksi WiFi gagal: ${d.message}`, "error");
      alert(d.message);
    }
  } catch (e) {
    logTerm("Kesalahan koneksi WiFi: " + e.message, "error");
  }
}
</script>
@endsection

