@extends('layouts.app')

@section('content')
<div class="vehicle-selector-bar">
  <div>
    <h2 style="font-size: 20px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
      <span>🧪</span> VIRTUAL ECU & TELEMATICS TEST RIG (SIMULATOR)
    </h2>
    <p style="font-size: 13px; color: var(--text-muted);">
      Alat simulasi pengiriman data sensor real-time IoT/ECU ke sistem MOTEC
    </p>
  </div>

  <div style="display: flex; gap: 10px; align-items: center;">
    <label for="simVehicleSelect" style="font-size: 12px; font-weight: 700; color: var(--text-muted);">MOTOR:</label>
    <select id="simVehicleSelect" class="select-control" style="min-width: 200px;" onchange="window.location.href='/simulator?vehicle_id=' + this.value">
      @foreach ($vehicles as $v)
      <option value="{{ $v->id }}" {{ $v->id == $vehicle_id ? 'selected' : '' }}>
        {{ $v->brand }} {{ $v->model }} ({{ $v->plate_number }})
      </option>
      @endforeach
    </select>
  </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
  
  <!-- LEFT: ENGINE CONTROL PANEL & SLIDERS -->
  <div class="glass-card">
    <div class="glass-card-header">
      <div class="glass-card-title">
        <span>🎮</span> KONTROL TELEMETRI HARDWARE
      </div>
      <div>
        <button id="btnAutoRide" class="btn btn-outline btn-sm" onclick="toggleAutoRide()">
          <span>🔄</span> Mode Auto-Ride: OFF
        </button>
      </div>
    </div>

    <div class="glass-card-body">
      <!-- THROTTLE / TPS SLIDER -->
      <div style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; margin-bottom: 6px;">
          <span>Posisi Bukaan Gas (Throttle / TPS):</span>
          <span id="lblTpsVal" style="color: var(--cyan-glow); font-family: var(--font-mono);">0%</span>
        </div>
        <input type="range" id="simTps" min="0" max="100" value="0" style="width: 100%; accent-color: var(--cyan-glow);" oninput="onThrottleChange(this.value)">
      </div>

      <!-- RPM & SPEED SLIDERS -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
        <div>
          <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 4px;">
            <span>RPM Mesin:</span>
            <span id="lblRpmVal" style="color: #fff; font-family: var(--font-mono);">0</span>
          </div>
          <input type="range" id="simRpm" min="0" max="12000" step="100" value="0" style="width: 100%;" oninput="updateParam('rpm', this.value)">
        </div>

        <div>
          <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 4px;">
            <span>Kecepatan (km/h):</span>
            <span id="lblSpeedVal" style="color: #fff; font-family: var(--font-mono);">0</span>
          </div>
          <input type="range" id="simSpeed" min="0" max="160" step="1" value="0" style="width: 100%;" oninput="updateParam('speed', this.value)">
        </div>
      </div>

      <!-- BATTERY & ENGINE TEMP SLIDERS -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
        <div>
          <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 4px;">
            <span>Aki (Volt):</span>
            <span id="lblBattVal" style="color: #fff; font-family: var(--font-mono);">12.6V</span>
          </div>
          <input type="range" id="simBatt" min="10.0" max="15.8" step="0.1" value="12.6" style="width: 100%;" oninput="updateParam('batt', this.value)">
        </div>

        <div>
          <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 4px;">
            <span>Suhu Radiator (°C):</span>
            <span id="lblTempVal" style="color: #fff; font-family: var(--font-mono);">85°C</span>
          </div>
          <input type="range" id="simTemp" min="30" max="125" step="1" value="85" style="width: 100%;" oninput="updateParam('temp', this.value)">
        </div>
      </div>

      <!-- OIL & TIRES SLIDERS -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
        <div>
          <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 4px;">
            <span>Sisa Oli (%):</span>
            <span id="lblOilVal" style="color: #fff; font-family: var(--font-mono);">90%</span>
          </div>
          <input type="range" id="simOil" min="5" max="100" step="1" value="90" style="width: 100%;" oninput="updateParam('oil', this.value)">
        </div>

        <div>
          <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 4px;">
            <span>Ban Depan (PSI):</span>
            <span id="lblFrontPsi" style="color: #fff; font-family: var(--font-mono);">29 PSI</span>
          </div>
          <input type="range" id="simFrontPsi" min="15" max="40" step="0.5" value="29" style="width: 100%;" oninput="updateParam('front_psi', this.value)">
        </div>
      </div>

      <!-- PRESET SCENARIOS -->
      <div style="margin-bottom: 20px;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">
          PRESET SKENARIO KONDISI MOTOR:
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
          <button class="btn btn-outline btn-sm" onclick="applyPreset('idling')">Kondisi Langsam (Idle)</button>
          <button class="btn btn-outline btn-sm" onclick="applyPreset('cruising')">Cruising (65 km/h)</button>
          <button class="btn btn-outline btn-sm" onclick="applyPreset('overheat')" style="color: #ff2a55;">Uji Overheat (112°C)</button>
          <button class="btn btn-outline btn-sm" onclick="applyPreset('dead_batt')" style="color: #ffb300;">Uji Aki Soak (11.0V)</button>
          <button class="btn btn-outline btn-sm" onclick="applyPreset('bad_oil')" style="color: #ffb300;">Uji Oli Kritis (15%)</button>
        </div>
      </div>

      <button class="btn btn-primary" style="width: 100%;" onclick="sendTelemetryPacket()">
        <span>📡</span> Kirim Paket Telemetri Sekarang (Push to ECU API)
      </button>

    </div>
  </div>

  <!-- RIGHT: LIVE HARDWARE JSON PACKET & REST/MQTT DOCUMENTATION -->
  <div style="display: flex; flex-direction: column; gap: 24px;">
    
    <!-- JSON PAYLOAD MONITOR -->
    <div class="glass-card">
      <div class="glass-card-header">
        <div class="glass-card-title">
          <span>📦</span> PAYLOAD JSON IOT TELEMATIKA (OUTGOING PACKET)
        </div>
      </div>
      <div class="glass-card-body" style="padding: 16px;">
        <pre id="jsonPreview" style="background: rgba(0,0,0,0.5); padding: 14px; border-radius: var(--radius-sm); font-family: var(--font-mono); font-size: 12px; color: var(--cyan-glow); overflow-x: auto; max-height: 220px;"></pre>
      </div>
    </div>

    <!-- HARDWARE CONNECTION GUIDE -->
    <div class="glass-card">
      <div class="glass-card-header">
        <div class="glass-card-title">
          <span>🔌</span> INTEGRASI HARDWARE ASLI (ESP32 / OBD-II / RASPBERRY PI)
        </div>
      </div>
      <div class="glass-card-body" style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
        <p style="margin-bottom: 10px;">
          Untuk menghubungkan motor sungguhan Anda ke sistem <strong>MOTEC</strong>:
        </p>
        <ol style="padding-left: 20px; margin-bottom: 14px;">
          <li>Pasang modul <strong>ESP32 CAN-Bus / K-Line transceiver</strong> ke port OBD2 / DLC motor Anda.</li>
          <li>Kirimkan payload HTTP POST secara berkala (tiap 500ms - 1 detik) ke endpoint:</li>
        </ol>

        <div style="background: var(--bg-surface-elevated); padding: 10px 14px; border-radius: var(--radius-sm); font-family: var(--font-mono); font-size: 12px; color: #fff; margin-bottom: 12px; border: 1px solid var(--border-subtle);">
          POST http://&lt;IP_SERVER&gt;:8000/api/telemetry/ingest
        </div>

        <p>
          Atau gunakan script standalone Python: <br>
          <code style="color: var(--cyan-glow); font-family: var(--font-mono);">python motec.py/simulator/iot_telematics_simulator.py --vehicle-id {{ $vehicle_id }}</code>
        </p>
      </div>
    </div>

  </div>

</div>
@endsection

@section('scripts')
<script>
  const vehicleId = {{ $vehicle_id }};
  let autoRideInterval = null;
  let isAutoRide = false;

  let currentPacket = {
    vehicle_id: vehicleId,
    engine_rpm: 0,
    speed_kmh: 0.0,
    engine_temp_c: 85.0,
    battery_voltage_v: 12.6,
    tps_throttle_percent: 0.0,
    gear_position: 0,
    oil_health_percent: 90.0,
    front_tire_psi: 29.0,
    rear_tire_psi: 33.0,
    o2_sensor_v: 0.45,
    fuel_level_percent: 75.0,
    odometer_km: 12000.0,
    dtc_codes: []
  };

  function updatePreview() {
    document.getElementById('jsonPreview').innerText = JSON.stringify(currentPacket, null, 2);
  }

  function onThrottleChange(val) {
    const tps = parseFloat(val);
    document.getElementById('lblTpsVal').innerText = `${tps}%`;
    currentPacket.tps_throttle_percent = tps;

    // Hitung RPM & Speed realistis berdasarkan gas
    if (tps === 0) {
      currentPacket.engine_rpm = 1500; // idle
      currentPacket.speed_kmh = 0;
      currentPacket.gear_position = 0;
      currentPacket.battery_voltage_v = 13.8;
    } else {
      currentPacket.engine_rpm = Math.round(1500 + (tps / 100) * 8500);
      currentPacket.speed_kmh = Math.round((tps / 100) * 115);
      currentPacket.gear_position = Math.min(6, Math.max(1, Math.floor(tps / 18) + 1));
      currentPacket.battery_voltage_v = 14.2;
    }

    document.getElementById('simRpm').value = currentPacket.engine_rpm;
    document.getElementById('lblRpmVal').innerText = currentPacket.engine_rpm;

    document.getElementById('simSpeed').value = currentPacket.speed_kmh;
    document.getElementById('lblSpeedVal').innerText = currentPacket.speed_kmh;

    document.getElementById('simBatt').value = currentPacket.battery_voltage_v;
    document.getElementById('lblBattVal').innerText = `${currentPacket.battery_voltage_v}V`;

    updatePreview();
    sendTelemetryPacket();
  }

  function updateParam(field, val) {
    const fVal = parseFloat(val);
    if (field === 'rpm') {
      currentPacket.engine_rpm = parseInt(val);
      document.getElementById('lblRpmVal').innerText = val;
    } else if (field === 'speed') {
      currentPacket.speed_kmh = fVal;
      document.getElementById('lblSpeedVal').innerText = fVal;
    } else if (field === 'batt') {
      currentPacket.battery_voltage_v = fVal;
      document.getElementById('lblBattVal').innerText = `${fVal}V`;
    } else if (field === 'temp') {
      currentPacket.engine_temp_c = fVal;
      document.getElementById('lblTempVal').innerText = `${fVal}°C`;
    } else if (field === 'oil') {
      currentPacket.oil_health_percent = fVal;
      document.getElementById('lblOilVal').innerText = `${fVal}%`;
    } else if (field === 'front_psi') {
      currentPacket.front_tire_psi = fVal;
      document.getElementById('lblFrontPsi').innerText = `${fVal} PSI`;
    }
    updatePreview();
  }

  function applyPreset(name) {
    if (name === 'idling') {
      currentPacket.engine_rpm = 1600;
      currentPacket.speed_kmh = 0;
      currentPacket.tps_throttle_percent = 0;
      currentPacket.engine_temp_c = 85.0;
      currentPacket.battery_voltage_v = 14.1;
      currentPacket.gear_position = 0;
    } else if (name === 'cruising') {
      currentPacket.engine_rpm = 5800;
      currentPacket.speed_kmh = 65.0;
      currentPacket.tps_throttle_percent = 35.0;
      currentPacket.engine_temp_c = 88.0;
      currentPacket.battery_voltage_v = 14.2;
      currentPacket.gear_position = 4;
    } else if (name === 'overheat') {
      currentPacket.engine_temp_c = 112.5;
      currentPacket.engine_rpm = 4000;
    } else if (name === 'dead_batt') {
      currentPacket.battery_voltage_v = 11.0;
      currentPacket.engine_rpm = 0;
    } else if (name === 'bad_oil') {
      currentPacket.oil_health_percent = 15.0;
    }

    // Sync input sliders
    document.getElementById('simRpm').value = currentPacket.engine_rpm;
    document.getElementById('lblRpmVal').innerText = currentPacket.engine_rpm;
    document.getElementById('simSpeed').value = currentPacket.speed_kmh;
    document.getElementById('lblSpeedVal').innerText = currentPacket.speed_kmh;
    document.getElementById('simTemp').value = currentPacket.engine_temp_c;
    document.getElementById('lblTempVal').innerText = `${currentPacket.engine_temp_c}°C`;
    document.getElementById('simBatt').value = currentPacket.battery_voltage_v;
    document.getElementById('lblBattVal').innerText = `${currentPacket.battery_voltage_v}V`;
    document.getElementById('simOil').value = currentPacket.oil_health_percent;
    document.getElementById('lblOilVal').innerText = `${currentPacket.oil_health_percent}%`;

    updatePreview();
    sendTelemetryPacket();
    window.Motec.showToast(`Preset [${name}] diterapkan!`, 'info');
  }

  async function sendTelemetryPacket() {
    try {
      const res = await fetch('/api/telemetry/ingest', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(currentPacket)
      });
      if (res.ok) {
        // Success
      }
    } catch (e) {
      console.error(e);
    }
  }

  function toggleAutoRide() {
    const btn = document.getElementById('btnAutoRide');
    isAutoRide = !isAutoRide;
    if (isAutoRide) {
      btn.innerText = '🔄 Mode Auto-Ride: ON';
      btn.style.color = '#00e676';
      btn.style.borderColor = '#00e676';
      
      let step = 0;
      autoRideInterval = setInterval(() => {
        step += 0.1;
        const wave = (Math.sin(step) + 1) / 2; // 0..1
        const tps = Math.round(wave * 70);
        onThrottleChange(tps);
      }, 700);
      window.Motec.showToast('Simulasi Auto-Ride Aktif!', 'success');
    } else {
      btn.innerText = '🔄 Mode Auto-Ride: OFF';
      btn.style.color = '';
      btn.style.borderColor = '';
      clearInterval(autoRideInterval);
      onThrottleChange(0);
      window.Motec.showToast('Simulasi Auto-Ride Dihentikan', 'info');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    updatePreview();
  });
</script>
@endsection
