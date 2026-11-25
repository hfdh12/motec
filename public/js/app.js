// ==============================================================================
// MOTEC Application Core Helpers
// ==============================================================================

window.Motec = {
  // Format mata uang rupiah
  formatIDR(num) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num);
  },

  // Toast notification
  showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `motec-toast toast-${type}`;
    toast.style.cssText = `
      position: fixed;
      bottom: 85px;
      right: 20px;
      z-index: 9999;
      background: #162035;
      color: #fff;
      padding: 12px 20px;
      border-radius: 8px;
      border: 1px solid rgba(0, 240, 255, 0.4);
      box-shadow: 0 10px 25px rgba(0,0,0,0.6);
      font-size: 13px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
      animation: slide-up 0.3s ease;
    `;
    
    let icon = '';
    if (type === 'success') {
      icon = '';
      toast.style.borderColor = '#00e676';
    } else if (type === 'danger') {
      icon = '';
      toast.style.borderColor = '#ff2a55';
    } else if (type === 'warning') {
      icon = '';
      toast.style.borderColor = '#ffb300';
    }

    toast.innerHTML = `<span>${icon}</span><span>${message}</span>`;
    document.body.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity 0.4s';
      setTimeout(() => toast.remove(), 400);
    }, 3500);
  },

  // Color helper berdasarkan skor kesehatan
  getScoreColor(score) {
    if (score >= 90) return '#00e676'; // Hijau
    if (score >= 75) return '#00f0ff'; // Biru Cyan
    if (score >= 55) return '#ffb300'; // Kuning Amber
    return '#ff2a55'; // Merah Kritis
  },

  // Update circular score gauge SVG
  renderScoreRing(svgElementId, textElementId, score) {
    const circle = document.getElementById(svgElementId);
    const textEl = document.getElementById(textElementId);
    if (!circle || !textEl) return;

    const radius = 70;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (score / 100) * circumference;

    circle.style.strokeDasharray = `${circumference}`;
    circle.style.strokeDashoffset = `${offset}`;
    circle.style.stroke = this.getScoreColor(score);
    textEl.innerText = `${Math.round(score)}%`;
    textEl.style.color = this.getScoreColor(score);
  }
};
