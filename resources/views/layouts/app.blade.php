<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>{{ $page_title ?? 'MOTEC - Motor Technical Check' }}</title>
  <meta name="description" content="Sistem Pengecekan Teknis Sepeda Motor Mandiri Terintegrasi Sensor & ECU Real-Time">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700;800&display=swap">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏍️</text></svg>">
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

  <!-- TOP NAVBAR -->
  <header class="navbar">
    <a href="{{ route('dashboard') }}" class="brand-container">
      <div class="brand-logo">M</div>
      <div class="brand-title">
        <span>MOTEC</span>
        <span class="tagline">Motor Technical Check</span>
      </div>
    </a>

    @php
      $currentVehicleId = $selected_id ?? ($vehicle_id ?? ($vehicle->id ?? 1));
    @endphp

    <nav class="nav-links">
      <a href="{{ route('dashboard', ['vehicle_id' => $currentVehicleId]) }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <span>📊</span> Dashboard
      </a>
      <a href="{{ route('diagnostics.show', $currentVehicleId) }}" class="nav-link {{ request()->routeIs('diagnostics.show') ? 'active' : '' }}">
        <span>🩺</span> Pengecekan Teknis
      </a>
      <a href="{{ route('live_hud.show', $currentVehicleId) }}" class="nav-link {{ request()->routeIs('live_hud.show') ? 'active' : '' }}">
        <span>⚡</span> Live Cockpit HUD
      </a>
      <a href="{{ route('dtc_scanner.show', $currentVehicleId) }}" class="nav-link {{ request()->routeIs('dtc_scanner.show') ? 'active' : '' }}">
        <span>⚠️</span> Scanner DTC
      </a>
      <a href="{{ route('elm327.show', $currentVehicleId) }}" class="nav-link {{ request()->routeIs('elm327.show') ? 'active' : '' }}">
        <span>🔌</span> ELM327 OBD-II
      </a>
      <a href="{{ route('simulator.show', ['vehicle_id' => $currentVehicleId]) }}" class="nav-link {{ request()->routeIs('simulator.show') ? 'active' : '' }}">
        <span>🧪</span> Simulator ECU
      </a>
      <a href="{{ route('maintenance.show', $currentVehicleId) }}" class="nav-link {{ request()->routeIs('maintenance.show') ? 'active' : '' }}">
        <span>🔧</span> Riwayat Servis
      </a>
    </nav>

    <div style="display: flex; align-items: center; gap: 12px;">
      <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600;">
        <span class="pulse-dot online" id="wsStatusDot"></span>
        <span id="wsStatusLabel" style="color: #00e676;">ECU Ready</span>
      </div>
    </div>
  </header>

  <!-- MAIN CONTENT CONTAINER -->
  <main class="container">
    @yield('content')
  </main>

  <!-- MOBILE BOTTOM BAR (Khusus Smartphone) -->
  <nav class="mobile-bottom-nav">
    <a href="{{ route('dashboard', ['vehicle_id' => $currentVehicleId]) }}" class="mobile-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <span class="mobile-nav-icon">📊</span>
      <span>Dashboard</span>
    </a>
    <a href="{{ route('diagnostics.show', $currentVehicleId) }}" class="mobile-nav-item {{ request()->routeIs('diagnostics.show') ? 'active' : '' }}">
      <span class="mobile-nav-icon">🩺</span>
      <span>Pengecekan</span>
    </a>
    <a href="{{ route('live_hud.show', $currentVehicleId) }}" class="mobile-nav-item {{ request()->routeIs('live_hud.show') ? 'active' : '' }}">
      <span class="mobile-nav-icon">⚡</span>
      <span>Live HUD</span>
    </a>
    <a href="{{ route('dtc_scanner.show', $currentVehicleId) }}" class="mobile-nav-item {{ request()->routeIs('dtc_scanner.show') ? 'active' : '' }}">
      <span class="mobile-nav-icon">⚠️</span>
      <span>DTC Error</span>
    </a>
    <a href="{{ route('elm327.show', $currentVehicleId) }}" class="mobile-nav-item {{ request()->routeIs('elm327.show') ? 'active' : '' }}">
      <span class="mobile-nav-icon">🔌</span>
      <span>ELM327</span>
    </a>
    <a href="{{ route('simulator.show', ['vehicle_id' => $currentVehicleId]) }}" class="mobile-nav-item {{ request()->routeIs('simulator.show') ? 'active' : '' }}">
      <span class="mobile-nav-icon">🧪</span>
      <span>Simulator</span>
    </a>
  </nav>

  <!-- CORE SCRIPTS -->
  <script src="{{ asset('js/app.js') }}"></script>
  <script src="{{ asset('js/live-telemetry.js') }}"></script>
  <script src="{{ asset('js/gauges.js') }}"></script>
  @yield('scripts')
</body>
</html>
