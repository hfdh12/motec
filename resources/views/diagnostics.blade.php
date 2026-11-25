@extends('layouts.app')

@section('content')
<div class="vehicle-selector-bar no-print">
  <div>
    <h2 style="font-size: 22px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 10px;">
      <span>🩺</span> Pengecekan Teknis Motor Mandiri (Motor Technical Check)
    </h2>
    <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
      Diagnosis otomatis berbasis parameter sensor ECU & telemetri IoT sepeda motor Anda
    </p>
  </div>

  <div style="display: flex; gap: 10px; align-items: center;">
    <select class="select-control" onchange="window.location.href='/diagnostics/' + this.value">
      @foreach ($vehicles as $v)
      <option value="{{ $v->id }}" {{ $v->id == $vehicle_id ? 'selected' : '' }}>
        {{ $v->brand }} {{ $v->model }} ({{ $v->plate_number }})
      </option>
      @endforeach
    </select>
    <button class="btn btn-primary" id="btnStartScan" onclick="triggerScanFlow()">
      <span>⚡</span> Jalankan Scanner Sekarang
    </button>
    <button class="btn btn-outline" onclick="window.print()">
      <span>🖨️</span> Cetak Lembar Uji (PDF)
    </button>
  </div>
</div>

<!-- STEP-BY-STEP SCANNER INTERACTIVE BOX -->
<div id="scanOverlayBox" class="glass-card no-print" style="display: none; margin-bottom: 24px; border-color: var(--cyan-glow);">
  <div class="scan-laser-beam"></div>
  <div class="glass-card-body" style="padding: 30px; text-align: center;">
    <div style="font-size: 48px; margin-bottom: 12px;" id="scannerIcon">🔍</div>
    <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 6px;" id="scannerPhaseTitle">
      Menghubungkan ke Port Telemetri ECU Motor...
    </h3>
    <p style="font-size: 13px; color: var(--cyan-glow); margin-bottom: 24px;" id="scannerPhaseDesc">
      Memindai parameter sensor K-Line/CAN-Bus sepeda motor...
    </p>

    <!-- Scan Progress Bar -->
    <div style="max-width: 500px; margin: 0 auto; background: rgba(255,255,255,0.08); height: 10px; border-radius: 5px; overflow: hidden; margin-bottom: 20px;">
      <div id="scannerBar" style="height: 100%; width: 0%; background: linear-gradient(90deg, #00f0ff, #00e676); transition: width 0.3s ease;"></div>
    </div>

    <!-- Check items list -->
    <div style="max-width: 450px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; text-align: left; font-size: 12px; font-weight: 600;">
      <div id="chk1" style="color: var(--text-dim);">○ Uji Koneksi Protokol ECU</div>
      <div id="chk2" style="color: var(--text-dim);">○ Pengecekan Suhu & Radiator</div>
      <div id="chk3" style="color: var(--text-dim);">○ Uji Tegangan Aki & Spul</div>
      <div id="chk4" style="color: var(--text-dim);">○ Evaluasi Oli & Tekanan Ban</div>
    </div>
  </div>
</div>

<!-- OFFICIAL INSPECTION REPORT SHEET (Printable & Screen) -->
<div class="glass-card" id="inspectionReportCard">
  <div class="glass-card-header" style="background: rgba(0, 240, 255, 0.05);">
    <div>
      <span style="font-size: 11px; font-weight: 800; color: var(--cyan-glow); letter-spacing: 1.5px; text-transform: uppercase;">
        LEMBAR HASIL UJI KELAIKAN & TEKNIK MOTOR (MOTEC REPORT)
      </span>
      <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 4px;">
        {{ $vehicle->brand }} {{ $vehicle->model }} — {{ $vehicle->plate_number }}
      </h3>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 11px; color: var(--text-muted);">Nomor Sesi Uji:</div>
      <div style="font-family: var(--font-mono); font-size: 13px; font-weight: 700; color: #fff;" id="reportSessionUid">
        MOTEC-LIVE-SYNC
      </div>
      <div style="font-size: 11px; color: var(--text-dim);" id="reportDate">
        -
      </div>
    </div>
  </div>

  <div class="glass-card-body">

    <!-- REPORT HERO: SCORE & OVERALL VERDICT -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border-subtle); margin-bottom: 24px; align-items: center;">
      
      <div style="display: flex; align-items: center; gap: 24px;">
        <div class="score-circle-wrapper" style="width: 140px; height: 140px;">
          <svg class="score-circle-svg" viewBox="0 0 160 160">
            <circle class="score-circle-bg" cx="80" cy="80" r="70"></circle>
            <circle id="reportScoreRing" class="score-circle-progress" cx="80" cy="80" r="70"></circle>
          </svg>
          <div class="score-number-center">
            <div id="reportScoreVal" class="score-val" style="font-size: 36px;">--%</div>
            <div class="score-label" style="font-size: 9px;">HASIL UJI</div>
          </div>
        </div>

        <div>
          <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
            Kesimpulan Teknis:
          </div>
          <div id="reportVerdictBadge" class="badge badge-info" style="font-size: 13px; padding: 6px 14px; margin-top: 6px;">
            Sedang Memuat...
          </div>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 8px;" id="reportOdoRead">
            Odometer: - km
          </div>
        </div>
      </div>

      <!-- RADAR / SUB-SCORES SUMMARY -->
      <div style="background: var(--bg-surface-elevated); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 12px; text-transform: uppercase;">
          Nilai Kelayakan per Sub-Sistem:
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12px;">
          <div>
            <div style="color: var(--text-muted);">Mesin & Injeksi:</div>
            <strong id="repScoreEngine" style="font-size: 16px; font-family: var(--font-mono); color: #fff;">-%</strong>
          </div>
          <div>
            <div style="color: var(--text-muted);">Kelistrikan & Aki:</div>
            <strong id="repScoreElectrical" style="font-size: 16px; font-family: var(--font-mono); color: #fff;">-%</strong>
          </div>
          <div>
            <div style="color: var(--text-muted);">Sistem Pendingin:</div>
            <strong id="repScoreCooling" style="font-size: 16px; font-family: var(--font-mono); color: #fff;">-%</strong>
          </div>
          <div>
            <div style="color: var(--text-muted);">Pelumasan Oli:</div>
            <strong id="repScoreOil" style="font-size: 16px; font-family: var(--font-mono); color: #fff;">-%</strong>
          </div>
          <div>
            <div style="color: var(--text-muted);">Ban & Pengereman:</div>
            <strong id="repScoreSafety" style="font-size: 16px; font-family: var(--font-mono); color: #fff;">-%</strong>
          </div>
          <div>
            <div style="color: var(--text-muted);">Kode Gangguan (DTC):</div>
            <strong id="repDtcCount" style="font-size: 16px; font-family: var(--font-mono); color: #fff;">0 Aktif</strong>
          </div>
        </div>
      </div>

    </div>

    <!-- SENSOR TELEMETRY COMPARISON TABLE -->
    <div style="margin-bottom: 24px;">
      <h4 style="font-size: 14px; font-weight: 700; color: #fff; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
        <span>📊</span> Tabel Pengukuran Sensor Real-Time vs Standar Pabrik
      </h4>

      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
          <thead>
            <tr style="border-bottom: 1px solid var(--border-subtle); color: var(--text-muted); text-align: left;">
              <th style="padding: 10px 12px;">Parameter Teknis</th>
              <th style="padding: 10px 12px;">Nilai Terbaca (Real)</th>
              <th style="padding: 10px 12px;">Standar Pabrikan</th>
              <th style="padding: 10px 12px;">Status</th>
            </tr>
          </thead>
          <tbody id="telemetryTableBody">
            <!-- Diisi secara dinamis -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- TEMUAN & REKOMENDASI PERBAIKAN -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
      
      <!-- Temuan Masalah -->
      <div style="background: rgba(255, 42, 85, 0.05); border: 1px solid rgba(255, 42, 85, 0.2); border-radius: var(--radius-md); padding: 18px;">
        <h4 style="font-size: 13px; font-weight: 700; color: var(--red-crit); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
          <span>⚠️</span> DAFTAR TEMUAN / ANOMALI SENSOR
        </h4>
        <ul id="findingsList" style="padding-left: 20px; font-size: 13px; color: var(--text-main); line-height: 1.6;">
          <li>Memuat temuan...</li>
        </ul>
      </div>

      <!-- Tindakan Rekomendasi Mandiri -->
      <div style="background: rgba(0, 230, 118, 0.05); border: 1px solid rgba(0, 230, 118, 0.2); border-radius: var(--radius-md); padding: 18px;">
        <h4 style="font-size: 13px; font-weight: 700; color: var(--green-ok); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
          <span>🔧</span> REKOMENDASI TINDAKAN & PERAWATAN
        </h4>
        <ul id="recommendationsList" style="padding-left: 20px; font-size: 13px; color: var(--text-main); line-height: 1.6;">
          <li>Memuat rekomendasi...</li>
        </ul>
      </div>

    </div>

  </div>
</div>
@endsection

@section('scripts')
<script>
  const vehicleId = {{ $vehicle_id }};

  async function loadDiagnosticsData() {
    try {
      const res = await fetch(`/api/vehicles/${vehicleId}`);
      const data = await res.json();
      renderReport(data.evaluation, data.active_dtcs, data.vehicle, data.latest_telemetry);
    } catch (e) {
      console.error(e);
    }
  }

  function renderReport(evalData, activeDtcs, vehicle, t) {
    if (!evalData) return;
    t = t || {};

    // Ring & Score
    if (window.Motec) {
      window.Motec.renderScoreRing('reportScoreRing', 'reportScoreVal', evalData.total_score);
    }

    // Verdict Badge
    const badge = document.getElementById('reportVerdictBadge');
    badge.className = `badge badge-${evalData.status_badge}`;
    badge.innerText = evalData.status_text;

    document.getElementById('reportDate').innerText = new Date().toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) + ' WIB';
    document.getElementById('reportOdoRead').innerText = `Odometer: ${Math.round(t.odometer_km || vehicle.current_odometer_km || 0).toLocaleString('id-ID')} km`;

    // Sub scores
    const s = evalData.sub_scores;
    if (s) {
      document.getElementById('repScoreEngine').innerText = `${s.engine}%`;
      document.getElementById('repScoreEngine').style.color = window.Motec.getScoreColor(s.engine);

      document.getElementById('repScoreElectrical').innerText = `${s.electrical}%`;
      document.getElementById('repScoreElectrical').style.color = window.Motec.getScoreColor(s.electrical);

      document.getElementById('repScoreCooling').innerText = `${s.cooling}%`;
      document.getElementById('repScoreCooling').style.color = window.Motec.getScoreColor(s.cooling);

      document.getElementById('repScoreOil').innerText = `${s.lubrication}%`;
      document.getElementById('repScoreOil').style.color = window.Motec.getScoreColor(s.lubrication);

      document.getElementById('repScoreSafety').innerText = `${s.safety}%`;
      document.getElementById('repScoreSafety').style.color = window.Motec.getScoreColor(s.safety);
    }

    const dtcList = activeDtcs || [];
    document.getElementById('repDtcCount').innerText = `${dtcList.length} Kode Aktif`;
    document.getElementById('repDtcCount').style.color = dtcList.length > 0 ? '#ff2a55' : '#00e676';

    // Telemetry Table
    const tbody = document.getElementById('telemetryTableBody');
    tbody.innerHTML = `
      <tr style="border-bottom: 1px solid var(--border-subtle);">
        <td style="padding: 10px 12px; font-weight: 600;">Tegangan Baterai / Aki</td>
        <td style="padding: 10px 12px; font-family: var(--font-mono); font-weight: 700;">${Number(t.battery_voltage_v || 0).toFixed(1)} V</td>
        <td style="padding: 10px 12px; color: var(--text-muted);">12.4V - 14.8V</td>
        <td style="padding: 10px 12px;">${getParamBadge(t.battery_voltage_v >= 12.0 && t.battery_voltage_v <= 15.0)}</td>
      </tr>
      <tr style="border-bottom: 1px solid var(--border-subtle);">
        <td style="padding: 10px 12px; font-weight: 600;">Suhu Kerja Mesin / ECT</td>
        <td style="padding: 10px 12px; font-family: var(--font-mono); font-weight: 700;">${Number(t.engine_temp_c || 0).toFixed(1)} °C</td>
        <td style="padding: 10px 12px; color: var(--text-muted);">75.0°C - 98.0°C</td>
        <td style="padding: 10px 12px;">${getParamBadge(t.engine_temp_c <= 100.0)}</td>
      </tr>
      <tr style="border-bottom: 1px solid var(--border-subtle);">
        <td style="padding: 10px 12px; font-weight: 600;">Kualitas / Masa Pakai Oli</td>
        <td style="padding: 10px 12px; font-family: var(--font-mono); font-weight: 700;">${Math.round(t.oil_health_percent || 0)} %</td>
        <td style="padding: 10px 12px; color: var(--text-muted);">&gt; 30% Sisa Umur</td>
        <td style="padding: 10px 12px;">${getParamBadge(t.oil_health_percent >= 30.0)}</td>
      </tr>
      <tr style="border-bottom: 1px solid var(--border-subtle);">
        <td style="padding: 10px 12px; font-weight: 600;">Tekanan Ban Depan & Belakang</td>
        <td style="padding: 10px 12px; font-family: var(--font-mono); font-weight: 700;">Depan: ${Number(t.front_tire_psi || 0).toFixed(1)} | Blkg: ${Number(t.rear_tire_psi || 0).toFixed(1)} PSI</td>
        <td style="padding: 10px 12px; color: var(--text-muted);">Depan 29 PSI / Blkg 33 PSI</td>
        <td style="padding: 10px 12px;">${getParamBadge(t.front_tire_psi >= 26 && t.rear_tire_psi >= 30)}</td>
      </tr>
      <tr style="border-bottom: 1px solid var(--border-subtle);">
        <td style="padding: 10px 12px; font-weight: 600;">Sensor Oksigen (O2 Lambda)</td>
        <td style="padding: 10px 12px; font-family: var(--font-mono); font-weight: 700;">${Number(t.o2_sensor_v || 0).toFixed(2)} V</td>
        <td style="padding: 10px 12px; color: var(--text-muted);">0.10V - 0.90V (Switching)</td>
        <td style="padding: 10px 12px;">${getParamBadge(t.o2_sensor_v >= 0.1 && t.o2_sensor_v <= 0.9)}</td>
      </tr>
    `;

    // Findings
    const findingsUl = document.getElementById('findingsList');
    if (evalData.findings && evalData.findings.length) {
      findingsUl.innerHTML = evalData.findings.map(f => `<li style="margin-bottom: 6px;">${f}</li>`).join('');
    } else {
      findingsUl.innerHTML = '<li style="color: #00e676;">Semua parameter sensor dalam batas toleransi normal pabrik.</li>';
    }

    // Recommendations
    const recoUl = document.getElementById('recommendationsList');
    if (evalData.recommendations && evalData.recommendations.length) {
      recoUl.innerHTML = evalData.recommendations.map(r => `<li style="margin-bottom: 6px;">${r}</li>`).join('');
    } else {
      recoUl.innerHTML = '<li style="color: #00e676;">Kondisi motor prima! Pertahankan servis rutin berkala setiap 2.000 - 3.000 km.</li>';
    }
  }

  function getParamBadge(isOk) {
    return isOk 
      ? '<span class="badge badge-success">NORMAL</span>'
      : '<span class="badge badge-danger">ANOMALI</span>';
  }

  // Interactive Live Scan Flow
  async function triggerScanFlow() {
    const box = document.getElementById('scanOverlayBox');
    const bar = document.getElementById('scannerBar');
    const pTitle = document.getElementById('scannerPhaseTitle');
    const pDesc = document.getElementById('scannerPhaseDesc');
    const chk1 = document.getElementById('chk1');
    const chk2 = document.getElementById('chk2');
    const chk3 = document.getElementById('chk3');
    const chk4 = document.getElementById('chk4');

    box.style.display = 'block';
    bar.style.width = '0%';
    chk1.style.color = 'var(--text-dim)'; chk1.innerText = '○ Uji Koneksi Protokol ECU';
    chk2.style.color = 'var(--text-dim)'; chk2.innerText = '○ Pengecekan Suhu & Radiator';
    chk3.style.color = 'var(--text-dim)'; chk3.innerText = '○ Uji Tegangan Aki & Spul';
    chk4.style.color = 'var(--text-dim)'; chk4.innerText = '○ Evaluasi Oli & Tekanan Ban';

    // Step 1
    pTitle.innerText = 'Tahap 1: Memverifikasi Koneksi Telematika ECU...';
    pDesc.innerText = 'Membaca port K-Line / CAN-Bus perangkat...';
    bar.style.width = '25%';
    await wait(500);
    chk1.style.color = '#00e676'; chk1.innerText = '✓ Protokol ECU Terhubung';

    // Step 2
    pTitle.innerText = 'Tahap 2: Menganalisis Suhu Mesin & Pendingin...';
    pDesc.innerText = 'Mengevaluasi pembacaan ECT dan siklus kipas radiator...';
    bar.style.width = '50%';
    await wait(500);
    chk2.style.color = '#00e676'; chk2.innerText = '✓ Sistem Pendingin Terverifikasi';

    // Step 3
    pTitle.innerText = 'Tahap 3: Menguji Kelistrikan Aki & Regulator Kiprok...';
    pDesc.innerText = 'Menganalisis fluktuasi voltase beban starter dan putaran langsam...';
    bar.style.width = '75%';
    await wait(500);
    chk3.style.color = '#00e676'; chk3.innerText = '✓ Sistem Kelistrikan Selesai';

    // Step 4
    pTitle.innerText = 'Tahap 4: Menguji Keausan Fisik & Memindai DTC ECU...';
    pDesc.innerText = 'Memeriksa kualitas oli, tekanan ban, dan riwayat kode gangguan...';
    bar.style.width = '100%';
    await wait(600);
    chk4.style.color = '#00e676'; chk4.innerText = '✓ Pengecekan Selesai';

    // Call server to persist session
    try {
      const res = await fetch(`/api/diagnostics/run-scan/${vehicleId}`, { 
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });
      const scanRes = await res.json();
      document.getElementById('reportSessionUid').innerText = scanRes.session_uid;
      renderReport(scanRes.evaluation, scanRes.active_dtcs, scanRes.vehicle, scanRes.evaluation.telemetry_snapshot);
      window.Motec.showToast('Pemeriksaan Teknis Selesai & Disimpan!', 'success');
    } catch (e) {
      console.error(e);
    }

    await wait(400);
    box.style.display = 'none';
  }

  function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  document.addEventListener('DOMContentLoaded', () => {
    loadDiagnosticsData();
    new MotecTelemetryClient(vehicleId);
  });
</script>
@endsection
