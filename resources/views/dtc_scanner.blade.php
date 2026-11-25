@extends('layouts.app')

@section('content')
<div class="vehicle-selector-bar">
  <div>
    <h2 style="font-size: 20px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
      <span>⚠️</span> SCANNER KODE GANGGUAN ECU (DTC FAULT CODES)
    </h2>
    <div style="font-size: 13px; color: var(--text-muted);">
      {{ $vehicle->brand }} {{ $vehicle->model }} — <strong style="color: #fff;">{{ $vehicle->plate_number }}</strong>
    </div>
  </div>

  <div style="display: flex; gap: 10px; align-items: center;">
    <select class="select-control" onchange="window.location.href='/dtc-scanner/' + this.value">
      @foreach ($vehicles as $v)
      <option value="{{ $v->id }}" {{ $v->id == $vehicle_id ? 'selected' : '' }}>
        {{ $v->brand }} {{ $v->model }} ({{ $v->plate_number }})
      </option>
      @endforeach
    </select>
    <a href="{{ route('elm327.show', $vehicle_id) }}" class="btn btn-outline btn-sm">
      <span>🔌</span> Scan via ELM327
    </a>
    <button class="btn btn-danger btn-sm" onclick="clearFaults()">
      <span>🧹</span> Hapus DTC (Reset ECU)
    </button>
  </div>
</div>

<!-- MIL LAMP & STATUS HERO BANNER -->
<div class="glass-card" style="margin-bottom: 24px;">
  <div class="glass-card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
    
    <div style="display: flex; align-items: center; gap: 20px;">
      <!-- MIL CHECK ENGINE LAMP ICON -->
      <div id="milLampIcon" style="width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; background: rgba(0, 230, 118, 0.15); border: 2px solid #00e676; box-shadow: 0 0 20px rgba(0, 230, 118, 0.2);">
        🏍️
      </div>

      <div>
        <div style="font-size: 11px; font-weight: 800; color: var(--text-muted); letter-spacing: 1.5px; text-transform: uppercase;">
          STATUS INDIKATOR MIL (CHECK ENGINE)
        </div>
        <h3 id="milStatusTitle" style="font-size: 20px; font-weight: 800; color: #00e676; margin: 4px 0;">
          Sistem Injeksi Normal (MIL Mati)
        </h3>
        <p id="milStatusSub" style="font-size: 13px; color: var(--text-muted);">
          Tidak ada malfungsi sensor aktif yang terdeteksi pada memori ECU.
        </p>
      </div>
    </div>

    <!-- INJECTION TEST RIG ACCELERATOR -->
    <div style="background: var(--bg-surface-elevated); padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
      <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">
        🧪 UJI COBA INJEKSI GANGGUAN SENSOR (SIMULASI)
      </div>
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button class="btn btn-outline btn-sm" onclick="injectDtc('P0115')">+ Suhu ECT (P0115)</button>
        <button class="btn btn-outline btn-sm" onclick="injectDtc('P0562')">+ Aki Drop (P0562)</button>
        <button class="btn btn-outline btn-sm" onclick="injectDtc('P0120')">+ Sensor Gas (P0120)</button>
        <button class="btn btn-outline btn-sm" onclick="injectDtc('P0130')">+ Sensor O2 (P0130)</button>
      </div>
    </div>

  </div>
</div>

<!-- ACTIVE FAULTS CONTAINER -->
<h3 style="font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
  <span>🚨</span> KODE KERUSAKAN AKTIF SAAT INI (<span id="txtDtcActiveCount">0</span>)
</h3>

<div id="activeDtcList" style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 30px;">
  <div style="text-align: center; padding: 40px; color: var(--text-dim); background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
    Memuat data DTC dari ECU...
  </div>
</div>

<!-- MASTER DTC REFERENCE LIBRARY -->
<div class="glass-card">
  <div class="glass-card-header">
    <div class="glass-card-title">
      <span>📚</span> KAMUS MASTER KODE DIAGNOSIS GANGGUAN MOTOR (STANDAR OBD2 / MOTORCYCLE)
    </div>
  </div>
  <div class="glass-card-body" style="padding: 0;">
    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
          <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border-subtle); color: var(--text-muted); text-align: left;">
            <th style="padding: 12px 16px;">Kode DTC</th>
            <th style="padding: 12px 16px;">Kategori</th>
            <th style="padding: 12px 16px;">Nama Gangguan (Indonesia)</th>
            <th style="padding: 12px 16px;">Tingkat Bahaya</th>
            <th style="padding: 12px 16px;">Deskripsi & Solusi</th>
          </tr>
        </thead>
        <tbody id="dtcLibraryTable">
          <!-- Diisi via API -->
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  const vehicleId = {{ $vehicle_id }};

  async function loadDtcData() {
    try {
      const res = await fetch(`/api/vehicles/${vehicleId}`);
      const data = await res.json();
      renderActiveFaults(data.active_dtcs || []);

      const libRes = await fetch('/api/dtc/library');
      const libData = await libRes.json();
      renderLibrary(libData);
    } catch (e) {
      console.error(e);
    }
  }

  function renderActiveFaults(dtcs) {
    const list = document.getElementById('activeDtcList');
    const cnt = document.getElementById('txtDtcActiveCount');
    const milIcon = document.getElementById('milLampIcon');
    const milTitle = document.getElementById('milStatusTitle');
    const milSub = document.getElementById('milStatusSub');

    cnt.innerText = dtcs.length;

    if (!dtcs.length) {
      milIcon.style.borderColor = '#00e676';
      milIcon.style.boxShadow = '0 0 20px rgba(0, 230, 118, 0.3)';
      milTitle.style.color = '#00e676';
      milTitle.innerText = 'Sistem Injeksi Normal (MIL Mati)';
      milSub.innerText = 'Semua sensor motor beroperasi dalam parameter pabrik yang aman.';

      list.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #00e676; background: rgba(0, 230, 118, 0.05); border-radius: var(--radius-md); border: 1px solid rgba(0, 230, 118, 0.2);">
          <div style="font-size: 32px; margin-bottom: 8px;">✅</div>
          <div style="font-weight: 700; font-size: 15px;">Tidak Ditemukan Kode Gangguan Aktif</div>
          <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Sistem ECU motor Anda dalam kondisi prima tanpa error code.</div>
        </div>
      `;
      return;
    }

    // Ada masalah
    milIcon.style.borderColor = '#ff2a55';
    milIcon.style.boxShadow = '0 0 25px rgba(255, 42, 85, 0.5)';
    milTitle.style.color = '#ff2a55';
    milTitle.innerText = `Peringatan: ${dtcs.length} Kerusakan Terdeteksi (MIL Menyala!)`;
    milSub.innerText = 'Lampu Check Engine motor berkedip/menyala. Segera lakukan penanganan teknis di bawah ini.';

    list.innerHTML = dtcs.map(d => `
      <div class="glass-card" style="border-left: 4px solid ${d.severity === 'critical' ? 'var(--red-crit)' : 'var(--amber-warn)'};">
        <div class="glass-card-body" style="padding: 20px;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
            <div>
              <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-family: var(--font-mono); font-size: 20px; font-weight: 800; color: #fff;">${d.code}</span>
                <span class="badge ${d.severity === 'critical' ? 'badge-danger' : 'badge-warning'}">${d.severity}</span>
                <span style="font-size: 12px; color: var(--text-muted);">${d.category || 'ECU System'}</span>
              </div>
              <h4 style="font-size: 16px; font-weight: 700; color: #fff; margin-top: 6px;">${d.title_id}</h4>
              <p style="font-size: 13px; color: var(--text-muted); margin-top: 6px;">${d.description_id || '-'}</p>
            </div>
            <div style="font-size: 11px; color: var(--text-dim); text-align: right;">
              Terdeteksi: ${d.detected_at || 'Baru Saja'}
            </div>
          </div>

          <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border-subtle); background: rgba(0,0,0,0.2); padding: 12px 16px; border-radius: var(--radius-sm);">
            <strong style="color: var(--cyan-glow); font-size: 12px; text-transform: uppercase;">Langkah Solusi / Rekomendasi Mekanik:</strong>
            <p style="font-size: 13px; color: var(--text-main); margin-top: 4px;">${d.recommendation_id || 'Bawa motor ke bengkel untuk pengecekan multimeter.'}</p>
          </div>
        </div>
      </div>
    `).join('');
  }

  function renderLibrary(libs) {
    const tbody = document.getElementById('dtcLibraryTable');
    tbody.innerHTML = libs.map(l => `
      <tr style="border-bottom: 1px solid var(--border-subtle);">
        <td style="padding: 12px 16px; font-family: var(--font-mono); font-weight: 700; color: #fff;">${l.code}</td>
        <td style="padding: 12px 16px; color: var(--text-muted);">${l.category}</td>
        <td style="padding: 12px 16px; font-weight: 600;">${l.title_id}</td>
        <td style="padding: 12px 16px;">
          <span class="badge ${l.severity === 'critical' ? 'badge-danger' : (l.severity === 'warning' ? 'badge-warning' : 'badge-info')}">${l.severity}</span>
        </td>
        <td style="padding: 12px 16px; max-width: 400px; font-size: 12px; color: var(--text-muted);">
          <div>${l.description_id}</div>
          <div style="color: var(--cyan-glow); margin-top: 4px;"><strong>Solusi:</strong> ${l.recommendation_id}</div>
        </td>
      </tr>
    `).join('');
  }

  async function injectDtc(code) {
    try {
      const res = await fetch(`/api/dtc/inject/${vehicleId}?dtc_code=${code}`, { 
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });
      if (res.ok) {
        window.Motec.showToast(`Berhasil menyimulasikan kerusakan [${code}]!`, 'warning');
        loadDtcData();
      }
    } catch (e) {
      alert('Error: ' + e.message);
    }
  }

  async function clearFaults() {
    if (!confirm('Yakin ingin mereset memori ECU dan membersihkan semua kode kerusakan?')) return;
    try {
      const res = await fetch(`/api/dtc/clear/${vehicleId}`, { 
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });
      if (res.ok) {
        window.Motec.showToast('Semua kode DTC berhasil dibersihkan (ECU Reset OK)!', 'success');
        loadDtcData();
      }
    } catch (e) {
      alert('Error: ' + e.message);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    loadDtcData();
  });
</script>
@endsection
