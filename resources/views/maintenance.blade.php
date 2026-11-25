@extends('layouts.app')

@section('content')
<div class="vehicle-selector-bar">
  <div>
    <h2 style="font-size: 20px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
      <span>🔧</span> BUKU RIWAYAT SERVIS & PERAWATAN BERKALA
    </h2>
    <div style="font-size: 13px; color: var(--text-muted);">
      {{ $vehicle->brand }} {{ $vehicle->model }} — <strong style="color: #fff;">{{ $vehicle->plate_number }}</strong>
    </div>
  </div>

  <div style="display: flex; gap: 10px; align-items: center;">
    <select class="select-control" onchange="window.location.href='/maintenance/' + this.value">
      @foreach ($vehicles as $v)
      <option value="{{ $v->id }}" {{ $v->id == $vehicle_id ? 'selected' : '' }}>
        {{ $v->brand }} {{ $v->model }} ({{ $v->plate_number }})
      </option>
      @endforeach
    </select>
    <button class="btn btn-primary btn-sm" onclick="openServiceModal()">
      <span>➕</span> Catat Servis Baru
    </button>
  </div>
</div>

@if (session('success'))
<div style="background: rgba(0, 230, 118, 0.15); border: 1px solid var(--green-ok); border-radius: var(--radius-md); padding: 12px 18px; margin-bottom: 20px; color: #00e676; font-size: 13px; font-weight: 600;">
  ✅ {{ session('success') }}
</div>
@endif

<div class="glass-card">
  <div class="glass-card-header">
    <div class="glass-card-title">
      <span>📋</span> LOG PERAWATAN BENGKEL & PENGGANTIAN SPAREPART
    </div>
  </div>

  <div class="glass-card-body" style="padding: 0;">
    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
          <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border-subtle); color: var(--text-muted); text-align: left;">
            <th style="padding: 12px 16px;">Tanggal Servis</th>
            <th style="padding: 12px 16px;">Jenis Pekerjaan / Servis</th>
            <th style="padding: 12px 16px;">Odometer (km)</th>
            <th style="padding: 12px 16px;">Bengkel</th>
            <th style="padding: 12px 16px;">Biaya</th>
            <th style="padding: 12px 16px;">Catatan Mekanik</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($records as $r)
          <tr style="border-bottom: 1px solid var(--border-subtle);">
            <td style="padding: 12px 16px; font-family: var(--font-mono); font-weight: 600; color: #fff;">
              {{ \Carbon\Carbon::parse($r->service_date)->format('d M Y') }}
            </td>
            <td style="padding: 12px 16px; font-weight: 700; color: var(--cyan-glow);">
              {{ $r->service_type }}
            </td>
            <td style="padding: 12px 16px; font-family: var(--font-mono);">
              {{ number_format($r->odometer_km, 0, ',', '.') }} km
            </td>
            <td style="padding: 12px 16px; color: var(--text-main);">
              {{ $r->workshop_name ?? '-' }}
            </td>
            <td style="padding: 12px 16px; font-family: var(--font-mono); color: #00e676; font-weight: 700;">
              Rp {{ number_format($r->cost_idr, 0, ',', '.') }}
            </td>
            <td style="padding: 12px 16px; color: var(--text-muted); font-size: 12px;">
              {{ $r->notes ?? '-' }}
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" style="padding: 40px; text-align: center; color: var(--text-dim);">
              Belum ada riwayat servis tercatat untuk sepeda motor ini.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL TAMBAH SERVIS -->
<div id="serviceModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 2000; align-items: center; justify-content: center; padding: 20px;">
  <div class="glass-card" style="max-width: 500px; width: 100%; border-color: rgba(0, 230, 118, 0.4);">
    <div class="glass-card-header">
      <div class="glass-card-title">
        <span>🔧</span> Tambah Catatan Servis Berkala
      </div>
      <button onclick="closeServiceModal()" style="background: none; border: none; color: #fff; font-size: 20px; cursor: pointer;">✕</button>
    </div>
    <div class="glass-card-body">
      <form action="{{ route('maintenance.store', $vehicle_id) }}" method="POST">
        @csrf
        <div style="margin-bottom: 14px;">
          <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Jenis Pekerjaan Servis</label>
          <input type="text" name="service_type" required placeholder="Contoh: Ganti Oli Mesin & Filter Oli" class="select-control" style="width: 100%;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
          <div>
            <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Tanggal Servis</label>
            <input type="date" name="service_date" required value="{{ date('Y-m-d') }}" class="select-control" style="width: 100%;">
          </div>
          <div>
            <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Odometer (km)</label>
            <input type="number" step="0.1" name="odometer_km" required value="{{ $vehicle->current_odometer_km ?? 0 }}" class="select-control" style="width: 100%;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
          <div>
            <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Nama Bengkel</label>
            <input type="text" name="workshop_name" placeholder="Contoh: Bengkel Resmi / AHASS" class="select-control" style="width: 100%;">
          </div>
          <div>
            <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Total Biaya (Rp)</label>
            <input type="number" name="cost_idr" placeholder="150000" class="select-control" style="width: 100%;">
          </div>
        </div>

        <div style="margin-bottom: 20px;">
          <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Catatan Tambahan</label>
          <textarea name="notes" rows="3" placeholder="Part yang diganti, saran mekanik, dll..." class="select-control" style="width: 100%; resize: vertical;"></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn btn-outline" onclick="closeServiceModal()">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Catatan Servis</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  function openServiceModal() {
    document.getElementById('serviceModal').style.display = 'flex';
  }
  function closeServiceModal() {
    document.getElementById('serviceModal').style.display = 'none';
  }
</script>
@endsection
