@extends('layouts.app')

@section('content')
<div class="vehicle-selector-bar">
  <div>
    <h2 style="font-size: 20px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
      <span>⚡</span> MOTEC LIVE COCKPIT HUD — {{ $vehicle->brand }} {{ $vehicle->model }}
    </h2>
    <div style="font-size: 12px; color: var(--text-muted);">
      Plat: <strong style="color: #fff;">{{ $vehicle->plate_number }}</strong> | Telematics: <span style="color: var(--cyan-glow);">{{ $vehicle->telematics_unit_id }}</span>
    </div>
  </div>

  <div style="display: flex; gap: 10px;">
    <a href="{{ route('elm327.show', $vehicle_id) }}" class="btn btn-outline btn-sm">
      <span>🔌</span> Sambungkan ELM327
    </a>
    <button class="btn btn-outline btn-sm" onclick="toggleFullscreen()">
      <span>⛶</span> Fullscreen Mode
    </button>
    <a href="{{ route('simulator.show', ['vehicle_id' => $vehicle_id]) }}" class="btn btn-primary btn-sm">
      <span>🎮</span> Buka Simulator
    </a>
  </div>
</div>

<!-- RACING HUD CLUSTER CONTAINER -->
<div class="hud-cluster" id="hudContainer">
  
  <!-- LED SHIFT LIGHTS -->
  <div class="shift-light-bar">
    <div class="shift-led green"></div>
    <div class="shift-led green"></div>
    <div class="shift-led green"></div>
    <div class="shift-led yellow"></div>
    <div class="shift-led yellow"></div>
    <div class="shift-led red"></div>
    <div class="shift-led red"></div>
  </div>

  <!-- MAIN HUD DISPLAY: TACHOMETER CANVAS + SPEED & GEAR -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; align-items: center;">
    
    <!-- ANALOG CANVAS TACHOMETER GAUGE -->
    <div style="text-align: center; position: relative;">
      <canvas id="tachometerCanvas" width="300" height="260" style="max-width: 100%; height: auto;"></canvas>
    </div>

    <!-- DIGITAL SPEEDOMETER & GEAR -->
    <div class="hud-center-display">
      <div class="speed-box">
        <div id="valSpeed" class="speed-num">0</div>
        <div class="speed-unit">KM / JAM</div>
        <div style="margin-top: 8px; font-size: 12px; font-weight: 700; color: var(--text-muted);">
          ODO: <span id="valOdo" style="color: #fff; font-family: var(--font-mono);">-</span> KM
        </div>
      </div>

      <div class="gear-box">
        <div class="gear-label">GEAR</div>
        <div id="valGear" class="gear-num">N</div>
      </div>
    </div>

    <!-- THROTTLE & BATTERY & ENGINE TEMP QUICK GAUGES -->
    <div style="display: flex; flex-direction: column; gap: 14px; background: rgba(0,0,0,0.3); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
      
      <!-- TPS Throttle -->
      <div>
        <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">
          <span>THROTTLE / TPS</span>
          <span id="txtTpsVal" style="color: var(--cyan-glow);">0%</span>
        </div>
        <div style="height: 8px; background: rgba(255,255,255,0.08); border-radius: 4px; overflow: hidden;">
          <div id="barTps" style="width: 0%; height: 100%; background: #00f0ff; transition: width 0.1s ease;"></div>
        </div>
      </div>

      <!-- Battery Voltage -->
      <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background: var(--bg-surface-elevated); border-radius: var(--radius-sm);">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 18px;">🔋</span>
          <div>
            <div style="font-size: 10px; color: var(--text-muted); text-transform: uppercase;">Aki Motor</div>
            <div style="font-size: 12px; font-weight: 600;" id="txtBattStatus">Optimal</div>
          </div>
        </div>
        <div style="text-align: right;">
          <span id="valBattery" style="font-family: var(--font-mono); font-size: 20px; font-weight: 700; color: #fff;">12.6</span>
          <span style="font-size: 11px; color: var(--text-muted);">V</span>
        </div>
      </div>

      <!-- Coolant Temp -->
      <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background: var(--bg-surface-elevated); border-radius: var(--radius-sm);">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 18px;">🌡️</span>
          <div>
            <div style="font-size: 10px; color: var(--text-muted); text-transform: uppercase;">Suhu Mesin</div>
            <div style="font-size: 12px; font-weight: 600;" id="txtTempStatus">Normal</div>
          </div>
        </div>
        <div style="text-align: right;">
          <span id="valTemp" style="font-family: var(--font-mono); font-size: 20px; font-weight: 700; color: #fff;">85.0</span>
          <span style="font-size: 11px; color: var(--text-muted);">°C</span>
        </div>
      </div>

    </div>

  </div>

  <!-- BOTTOM SENSOR STRIP IN HUD -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-subtle);">
    <div style="background: rgba(255,255,255,0.03); padding: 10px; border-radius: var(--radius-sm); text-align: center;">
      <div style="font-size: 10px; color: var(--text-muted);">BAN DEPAN</div>
      <div style="font-family: var(--font-mono); font-size: 16px; font-weight: 700; color: #fff;"><span id="valFrontTire">29.0</span> <small style="font-size: 10px;">PSI</small></div>
    </div>
    <div style="background: rgba(255,255,255,0.03); padding: 10px; border-radius: var(--radius-sm); text-align: center;">
      <div style="font-size: 10px; color: var(--text-muted);">BAN BELAKANG</div>
      <div style="font-family: var(--font-mono); font-size: 16px; font-weight: 700; color: #fff;"><span id="valRearTire">33.0</span> <small style="font-size: 10px;">PSI</small></div>
    </div>
    <div style="background: rgba(255,255,255,0.03); padding: 10px; border-radius: var(--radius-sm); text-align: center;">
      <div style="font-size: 10px; color: var(--text-muted);">SISA OLI</div>
      <div style="font-family: var(--font-mono); font-size: 16px; font-weight: 700; color: #00e676;"><span id="valOil">90</span>%</div>
    </div>
    <div style="background: rgba(255,255,255,0.03); padding: 10px; border-radius: var(--radius-sm); text-align: center;">
      <div style="font-size: 10px; color: var(--text-muted);">BENSIN</div>
      <div style="font-family: var(--font-mono); font-size: 16px; font-weight: 700; color: var(--cyan-glow);"><span id="valFuel">75</span>%</div>
    </div>
  </div>

</div>
@endsection

@section('scripts')
<script>
  const vehicleId = {{ $vehicle_id }};
  let tachGauge = null;

  function toggleFullscreen() {
    const elem = document.getElementById('hudContainer');
    if (!document.fullscreenElement) {
      elem.requestFullscreen().catch(err => {
        alert(`Error attempting to enable fullscreen: ${err.message}`);
      });
    } else {
      document.exitFullscreen();
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Initialize Analog Canvas Tachometer
    tachGauge = new MotecTachometerGauge('tachometerCanvas', {
      maxRpm: 12000,
      redlineRpm: 9500
    });

    // Start Live Telemetry Client
    new MotecTelemetryClient(vehicleId, {
      onUpdate: (t) => {
        if (tachGauge && t.engine_rpm !== undefined) {
          tachGauge.setRpm(t.engine_rpm);
        }
        
        // Update voltage sub-status
        const battEl = document.getElementById('txtBattStatus');
        if (battEl && t.battery_voltage_v) {
          if (t.battery_voltage_v < 11.8) {
            battEl.innerText = 'Drop Kritis!';
            battEl.style.color = '#ff2a55';
          } else if (t.battery_voltage_v < 12.4) {
            battEl.innerText = 'Lemah';
            battEl.style.color = '#ffb300';
          } else {
            battEl.innerText = 'Optimal';
            battEl.style.color = '#00e676';
          }
        }

        // Update temperature sub-status
        const tempEl = document.getElementById('txtTempStatus');
        if (tempEl && t.engine_temp_c) {
          if (t.engine_temp_c >= 105) {
            tempEl.innerText = 'OVERHEAT!';
            tempEl.style.color = '#ff2a55';
          } else if (t.engine_temp_c >= 98) {
            tempEl.innerText = 'Tinggi';
            tempEl.style.color = '#ffb300';
          } else {
            tempEl.innerText = 'Normal';
            tempEl.style.color = '#00e676';
          }
        }
      }
    });
  });
</script>
@endsection
