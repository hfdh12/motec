@extends('layouts.app')

@section('content')
<!-- VEHICLE SWITCHER & HEADER -->
<div class="vehicle-selector-bar">
  <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
    <label for="vehicleSelect" style="font-size: 13px; font-weight: 700; color: var(--text-muted);">PILIH KENDARAAN:</label>
    <select id="vehicleSelect" class="select-control" onchange="window.location.href='/?vehicle_id=' + this.value">
      @foreach ($vehicles as $v)
      <option value="{{ $v->id }}" {{ $v->id == $selected_id ? 'selected' : '' }}>
        {{ $v->brand }} {{ $v->model }} ({{ $v->plate_number }})
      </option>
      @endforeach
    </select>
  </div>

  <div style="display: flex; gap: 10px;">
    <button class="btn btn-outline btn-sm" onclick="openAddModal()">
      <span>➕</span> Tambah Motor
    </button>
    <a href="{{ route('simulator.show', ['vehicle_id' => $selected_id]) }}" class="btn btn-outline btn-sm" style="border-color: rgba(139, 92, 246, 0.4); color: #c084fc;">
      <span>🧪</span> Test Rig Simulator
    </a>
  </div>
</div>

<!-- VEHICLE QUICK INFO BADGE STRIP -->
<div id="vehicleMetaStrip" style="display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; font-size: 13px; color: var(--text-muted);">
  <div style="background: var(--bg-surface); padding: 6px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
    <strong style="color: #fff;" id="vPlate">{{ $vehicle->plate_number ?? '-' }}</strong>
  </div>
  <div style="background: var(--bg-surface); padding: 6px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
    Kapasitas: <span style="color: #fff;" id="vEngine">{{ $vehicle->engine_displacement_cc ?? '-' }}</span> cc
  </div>
  <div style="background: var(--bg-surface); padding: 6px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
    Total Odometer: <span style="color: #fff; font-family: var(--font-mono);" id="valOdo">{{ number_format($vehicle->current_odometer_km ?? 0, 0, ',', '.') }}</span> km
  </div>
  <div style="background: var(--bg-surface); padding: 6px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
    IoT Unit ID: <span style="color: var(--cyan-glow); font-family: var(--font-mono);" id="vIot">{{ $vehicle->telematics_unit_id ?? '-' }}</span>
  </div>
</div>

<!-- ACTIVE FAULT ALERT BAR (JIKA ADA GANGGUAN) -->
<div id="activeFaultBanner" style="display: none; margin-bottom: 24px;"></div>

<!-- MAIN GRID: OVERALL HEALTH CARD & ACTION DECK -->
<div style="display: grid; grid-template-columns: 1fr; gap: 24px; margin-bottom: 24px;">
  
  <div class="glass-card">
    <div class="glass-card-header">
      <div class="glass-card-title">
        <span>🩺</span>
        <span>STATUS KESEHATAN TEKNIS MOTOR (MOTEC INDEX)</span>
      </div>
      <div>
        <span id="vehicleStatusBadge" class="badge badge-info">Menganalisis...</span>
      </div>
    </div>

    <div class="glass-card-body">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; align-items: center;">
        
        <!-- CIRCULAR SCORE DIAL -->
        <div style="text-align: center;">
          <div class="score-circle-wrapper">
            <svg class="score-circle-svg" viewBox="0 0 160 160">
              <circle class="score-circle-bg" cx="80" cy="80" r="70"></circle>
              <circle id="healthScoreRing" class="score-circle-progress" cx="80" cy="80" r="70"></circle>
            </svg>
            <div class="score-number-center">
              <div id="healthScoreText" class="score-val">--%</div>
              <div class="score-label">SKOR TOTAL</div>
            </div>
          </div>
          <p style="font-size: 12px; color: var(--text-dim); margin-top: 10px;">
            Dihitung otomatis dari kalkulasi sensor telemetri ECU secara real-time
          </p>
        </div>

        <!-- SUB-SYSTEM BREAKDOWN PROGRESS BARS -->
        <div>
          <h4 style="font-size: 13px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 14px;">
            Kondisi Sub-Sistem Kendaraan:
          </h4>

          <div class="health-meter-item">
            <div class="health-meter-header">
              <span>⚡ Kelistrikan & Baterai (Aki / Spul)</span>
              <span id="txtElectrical">--%</span>
            </div>
            <div class="health-track">
              <div id="barElectrical" class="health-fill" style="width: 0%;"></div>
            </div>
          </div>

          <div class="health-meter-item">
            <div class="health-meter-header">
              <span>⚙️ Mesin & Sistem Injeksi (RPM & TPS)</span>
              <span id="txtEngine">--%</span>
            </div>
            <div class="health-track">
              <div id="barEngine" class="health-fill" style="width: 0%;"></div>
            </div>
          </div>

          <div class="health-meter-item">
            <div class="health-meter-header">
              <span>❄️ Sistem Pendinginan (Suhu Radiator)</span>
              <span id="txtCooling">--%</span>
            </div>
            <div class="health-track">
              <div id="barCooling" class="health-fill" style="width: 0%;"></div>
            </div>
          </div>

          <div class="health-meter-item">
            <div class="health-meter-header">
              <span>🛢️ Pelumasan & Tekanan Oli Mesin</span>
              <span id="txtLubrication">--%</span>
            </div>
            <div class="health-track">
              <div id="barLubrication" class="health-fill" style="width: 0%;"></div>
            </div>
          </div>

          <div class="health-meter-item">
            <div class="health-meter-header">
              <span>🛡️ Ban & Kampas Pengereman</span>
              <span id="txtSafety">--%</span>
            </div>
            <div class="health-track">
              <div id="barSafety" class="health-fill" style="width: 0%;"></div>
            </div>
          </div>
        </div>

        <!-- QUICK ACTION PANEL -->
        <div style="display: flex; flex-direction: column; gap: 12px; justify-content: center; border-left: 1px solid var(--border-subtle); padding-left: 20px;">
          <a href="{{ route('diagnostics.show', $selected_id) }}" class="btn btn-primary" style="width: 100%;">
            <span>🩺</span> Mulai Pengecekan Lengkap
          </a>
          <a href="{{ route('live_hud.show', $selected_id) }}" class="btn btn-outline" style="width: 100%; border-color: rgba(0, 240, 255, 0.4); color: var(--cyan-glow);">
            <span>⚡</span> Buka Live Cockpit / HUD
          </a>
          <a href="{{ route('dtc_scanner.show', $selected_id) }}" class="btn btn-outline" style="width: 100%;">
            <span>⚠️</span> Periksa Kode Gangguan ECU
          </a>
          <a href="{{ route('maintenance.show', $selected_id) }}" class="btn btn-outline" style="width: 100%; border-color: rgba(0, 230, 118, 0.4); color: var(--green-ok);">
            <span>🔧</span> Buku Riwayat Servis
          </a>
        </div>

      </div>
    </div>
  </div>

</div>

<!-- REAL-TIME TELEMETRY SENSOR TILES -->
<h3 style="font-size: 15px; font-weight: 700; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
  <span>📡</span> DATA TELEMETRI SENSOR MOTOR TERKINI (REAL-TIME)
</h3>

<div class="telemetry-grid">
  <!-- RPM -->
  <div class="metric-card">
    <div class="metric-head">
      <span>Putaran Mesin</span>
      <span>⚙️</span>
    </div>
    <div class="metric-val-wrap">
      <div id="valRpm" class="metric-val">-</div>
      <div class="metric-unit">RPM</div>
    </div>
    <div class="metric-status" style="color: var(--cyan-glow);">Terkoneksi ECU</div>
  </div>

  <!-- SPEED -->
  <div class="metric-card">
    <div class="metric-head">
      <span>Kecepatan</span>
      <span>⚡</span>
    </div>
    <div class="metric-val-wrap">
      <div id="valSpeed" class="metric-val">-</div>
      <div class="metric-unit">km/h</div>
    </div>
    <div class="metric-status" style="color: var(--text-dim);">Sensor Roda / Hall</div>
  </div>

  <!-- BATTERY VOLTAGE -->
  <div class="metric-card">
    <div class="metric-head">
      <span>Tegangan Aki</span>
      <span>🔋</span>
    </div>
    <div class="metric-val-wrap">
      <div id="valBattery" class="metric-val">-</div>
      <div class="metric-unit">Volt</div>
    </div>
    <div id="batteryStatusText" class="metric-status" style="color: var(--green-ok);">Standar 12.4V - 14.5V</div>
  </div>

  <!-- ENGINE TEMPERATURE -->
  <div class="metric-card">
    <div class="metric-head">
      <span>Suhu Mesin / ECT</span>
      <span>🌡️</span>
    </div>
    <div class="metric-val-wrap">
      <div id="valTemp" class="metric-val">-</div>
      <div class="metric-unit">°C</div>
    </div>
    <div class="metric-status" style="color: var(--text-muted);">Optimal: 75°C - 98°C</div>
  </div>

  <!-- OIL HEALTH -->
  <div class="metric-card">
    <div class="metric-head">
      <span>Sisa Umur Oli</span>
      <span>🛢️</span>
    </div>
    <div class="metric-val-wrap">
      <div id="valOil" class="metric-val">-</div>
      <div class="metric-unit">%</div>
    </div>
    <div class="metric-status" style="color: var(--green-ok);">Kualitas Pelumasan</div>
  </div>

  <!-- FRONT TIRE PSI -->
  <div class="metric-card">
    <div class="metric-head">
      <span>Ban Depan</span>
      <span>🛞</span>
    </div>
    <div class="metric-val-wrap">
      <div id="valFrontTire" class="metric-val">-</div>
      <div class="metric-unit">PSI</div>
    </div>
    <div class="metric-status" style="color: var(--text-muted);">Standar: 29 PSI</div>
  </div>

  <!-- REAR TIRE PSI -->
  <div class="metric-card">
    <div class="metric-head">
      <span>Ban Belakang</span>
      <span>🛞</span>
    </div>
    <div class="metric-val-wrap">
      <div id="valRearTire" class="metric-val">-</div>
      <div class="metric-unit">PSI</div>
    </div>
    <div class="metric-status" style="color: var(--text-muted);">Standar: 33 PSI</div>
  </div>

  <!-- FUEL LEVEL -->
  <div class="metric-card">
    <div class="metric-head">
      <span>Level Bahan Bakar</span>
      <span>⛽</span>
    </div>
    <div class="metric-val-wrap">
      <div id="valFuel" class="metric-val">-</div>
      <div class="metric-unit">%</div>
    </div>
    <div class="metric-status" style="color: var(--cyan-glow);">Sensor Pelampung</div>
  </div>
</div>

<!-- MODAL TAMBAH MOTOR -->
<div id="addVehicleModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 2000; align-items: center; justify-content: center; padding: 20px;">
  <div class="glass-card" style="max-width: 500px; width: 100%; border-color: rgba(0, 240, 255, 0.4);">
    <div class="glass-card-header">
      <div class="glass-card-title">
        <span>🏍️</span> Daftarkan Sepeda Motor Baru
      </div>
      <button onclick="closeAddModal()" style="background: none; border: none; color: #fff; font-size: 20px; cursor: pointer;">✕</button>
    </div>
    <div class="glass-card-body">
      <form id="formAddVehicle" onsubmit="submitNewVehicle(event)">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
          <div>
            <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Pabrikan (Brand)</label>
            <input type="text" name="brand" required placeholder="Contoh: Honda / Yamaha" class="select-control" style="width: 100%;">
          </div>
          <div>
            <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Model Motor</label>
            <input type="text" name="model" required placeholder="Contoh: Aerox 155" class="select-control" style="width: 100%;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
          <div>
            <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Nomor Polisi (Plat)</label>
            <input type="text" name="plate_number" required placeholder="B 1234 XYZ" class="select-control" style="width: 100%; text-transform: uppercase;">
          </div>
          <div>
            <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Tahun Pembuatan</label>
            <input type="number" name="year" required value="2024" class="select-control" style="width: 100%;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px;">
          <div>
            <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Kapasitas Mesin (CC)</label>
            <input type="number" name="engine_displacement_cc" required value="155" class="select-control" style="width: 100%;">
          </div>
          <div>
            <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Sistem Pendingin</label>
            <select name="cooling_type" class="select-control" style="width: 100%;">
              <option value="liquid">Cairan / Radiator</option>
              <option value="air">Udara (Air-cooled)</option>
            </select>
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn btn-outline" onclick="closeAddModal()">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Kendaraan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  const selectedVehicleId = {{ $selected_id }};
  let telemetryClient = null;

  async function loadInitialData() {
    try {
      const res = await fetch(`/api/vehicles/${selectedVehicleId}`);
      if (!res.ok) throw new Error('Gagal memuat detail motor');
      const data = await res.json();
      
      const v = data.vehicle;
      const t = data.latest_telemetry || {};
      const ev = data.evaluation;
      const dtcs = data.active_dtcs || [];

      // Update meta bar
      document.getElementById('vPlate').innerText = v.plate_number;
      document.getElementById('vEngine').innerText = v.engine_displacement_cc;
      document.getElementById('valOdo').innerText = Math.round(t.odometer_km || v.current_odometer_km || 0).toLocaleString('id-ID');
      document.getElementById('vIot').innerText = v.telematics_unit_id;

      // Update initial evaluation
      if (ev && window.Motec) {
        window.Motec.renderScoreRing('healthScoreRing', 'healthScoreText', ev.total_score);

        const statusBadge = document.getElementById('vehicleStatusBadge');
        if (statusBadge) {
          statusBadge.className = `badge badge-${ev.status_badge}`;
          statusBadge.innerText = ev.status_text;
        }

        // Sub scores
        const subs = ev.sub_scores;
        if (subs) {
          updateSub('barEngine', 'txtEngine', subs.engine);
          updateSub('barElectrical', 'txtElectrical', subs.electrical);
          updateSub('barCooling', 'txtCooling', subs.cooling);
          updateSub('barLubrication', 'txtLubrication', subs.lubrication);
          updateSub('barSafety', 'txtSafety', subs.safety);
        }
      }

      // Fault banner
      renderFaultBanner(dtcs);

    } catch (err) {
      console.error(err);
    }
  }

  function updateSub(barId, txtId, score) {
    const bar = document.getElementById(barId);
    const txt = document.getElementById(txtId);
    if (bar) {
      bar.style.width = `${score}%`;
      bar.style.backgroundColor = window.Motec.getScoreColor(score);
    }
    if (txt) {
      txt.innerText = `${score}%`;
      txt.style.color = window.Motec.getScoreColor(score);
    }
  }

  function renderFaultBanner(dtcs) {
    const banner = document.getElementById('activeFaultBanner');
    if (!banner) return;

    if (!dtcs.length) {
      banner.style.display = 'none';
      return;
    }

    banner.style.display = 'block';
    let html = `
      <div style="background: rgba(255, 42, 85, 0.15); border: 1px solid var(--red-crit); border-radius: var(--radius-md); padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <span style="font-size: 24px;">⚠️</span>
          <div>
            <div style="font-weight: 700; color: #ff2a55;">Terdeteksi ${dtcs.length} Kode Gangguan Teknis (DTC) Aktif!</div>
            <div style="font-size: 13px; color: var(--text-muted);">${dtcs.map(d => `<strong>[${d.code}]</strong> ${d.title_id}`).join(' | ')}</div>
          </div>
        </div>
        <div>
          <a href="/dtc-scanner/${selectedVehicleId}" class="btn btn-danger btn-sm">Periksa Kerusakan</a>
        </div>
      </div>
    `;
    banner.innerHTML = html;
  }

  function openAddModal() {
    document.getElementById('addVehicleModal').style.display = 'flex';
  }

  function closeAddModal() {
    document.getElementById('addVehicleModal').style.display = 'none';
  }

  async function submitNewVehicle(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const payload = {
      brand: formData.get('brand'),
      model: formData.get('model'),
      plate_number: formData.get('plate_number'),
      year: parseInt(formData.get('year')),
      engine_displacement_cc: parseInt(formData.get('engine_displacement_cc')),
      cooling_type: formData.get('cooling_type')
    };

    try {
      const res = await fetch('/api/vehicles', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(payload)
      });
      if (res.ok) {
        window.Motec.showToast('Motor berhasil didaftarkan!', 'success');
        setTimeout(() => window.location.reload(), 1000);
      } else {
        const err = await res.json();
        alert('Gagal: ' + (err.detail || err.message || 'Terjadi kesalahan'));
      }
    } catch (err) {
      alert('Error: ' + err.message);
    }
  }

  // Jalankan saat DOM siap
  document.addEventListener('DOMContentLoaded', () => {
    loadInitialData();
    telemetryClient = new MotecTelemetryClient(selectedVehicleId);
  });
</script>
@endsection
