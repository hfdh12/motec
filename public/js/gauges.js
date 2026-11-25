// ==============================================================================
// MOTEC Canvas Gauge Engine (Analog Tachometer & Dynamic Arc)
// ==============================================================================

class MotecTachometerGauge {
  constructor(canvasId, options = {}) {
    this.canvas = document.getElementById(canvasId);
    if (!this.canvas) return;
    this.ctx = this.canvas.getContext('2d');
    this.maxRpm = options.maxRpm || 12000;
    this.redlineRpm = options.redlineRpm || 9500;
    this.currentRpm = 0;
    this.targetRpm = 0;
    this.animationId = null;

    this.startLoop();
  }

  setRpm(rpm) {
    this.targetRpm = Math.max(0, Math.min(this.maxRpm, rpm));
  }

  startLoop() {
    const loop = () => {
      // Smooth lerp easing
      this.currentRpm += (this.targetRpm - this.currentRpm) * 0.2;
      this.draw();
      this.animationId = requestAnimationFrame(loop);
    };
    loop();
  }

  draw() {
    const ctx = this.ctx;
    const width = this.canvas.width;
    const height = this.canvas.height;
    const centerX = width / 2;
    const centerY = height / 2 + 10;
    const radius = Math.min(width, height) / 2 - 25;

    ctx.clearRect(0, 0, width, height);

    // Arc angles (from 135 deg to 45 deg clockwise = 270 deg span)
    const startAngle = Math.PI * 0.75;
    const endAngle = Math.PI * 2.25;
    const totalSpan = endAngle - startAngle;

    // 1. Background Arc Track
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, startAngle, endAngle);
    ctx.lineWidth = 14;
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.08)';
    ctx.lineCap = 'round';
    ctx.stroke();

    // 2. Redline Zone Arc (Misal 9,500 - 12,000 RPM)
    const redlineStartRatio = this.redlineRpm / this.maxRpm;
    const redlineAngle = startAngle + (totalSpan * redlineStartRatio);

    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, redlineAngle, endAngle);
    ctx.lineWidth = 14;
    ctx.strokeStyle = 'rgba(255, 42, 85, 0.35)';
    ctx.lineCap = 'round';
    ctx.stroke();

    // 3. Active RPM Arc Fill
    const currentRatio = Math.max(0, Math.min(1, this.currentRpm / this.maxRpm));
    const currentAngle = startAngle + (totalSpan * currentRatio);

    if (currentRatio > 0.01) {
      ctx.beginPath();
      ctx.arc(centerX, centerY, radius, startAngle, currentAngle);
      ctx.lineWidth = 14;
      
      const grad = ctx.createLinearGradient(0, centerY - radius, width, centerY + radius);
      grad.addColorStop(0, '#00f0ff');
      grad.addColorStop(0.7, '#00e676');
      grad.addColorStop(1, '#ff2a55');

      ctx.strokeStyle = grad;
      ctx.lineCap = 'round';
      ctx.shadowColor = this.currentRpm > this.redlineRpm ? '#ff2a55' : '#00f0ff';
      ctx.shadowBlur = 12;
      ctx.stroke();
      ctx.shadowBlur = 0; // reset
    }

    // 4. Tick Marks & Numbers
    const stepCount = 12;
    for (let i = 0; i <= stepCount; i++) {
      const ratio = i / stepCount;
      const angle = startAngle + (totalSpan * ratio);
      const isMajor = i % 2 === 0;

      const innerR = radius - (isMajor ? 20 : 12);
      const outerR = radius - 8;

      const x1 = centerX + Math.cos(angle) * innerR;
      const y1 = centerY + Math.sin(angle) * innerR;
      const x2 = centerX + Math.cos(angle) * outerR;
      const y2 = centerY + Math.sin(angle) * outerR;

      ctx.beginPath();
      ctx.moveTo(x1, y1);
      ctx.lineTo(x2, y2);
      ctx.lineWidth = isMajor ? 2.5 : 1.2;
      ctx.strokeStyle = (i * (this.maxRpm / stepCount)) >= this.redlineRpm ? '#ff2a55' : 'rgba(255, 255, 255, 0.4)';
      ctx.stroke();

      // Number labels
      if (isMajor) {
        const textR = radius - 32;
        const tx = centerX + Math.cos(angle) * textR;
        const ty = centerY + Math.sin(angle) * textR;
        ctx.fillStyle = (i * (this.maxRpm / stepCount)) >= this.redlineRpm ? '#ff2a55' : '#94a3b8';
        ctx.font = '600 11px JetBrains Mono, monospace';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(i, tx, ty);
      }
    }

    // 5. Center Dial Readout
    ctx.fillStyle = '#ffffff';
    ctx.font = '700 24px JetBrains Mono, monospace';
    ctx.textAlign = 'center';
    ctx.fillText(Math.round(this.currentRpm).toLocaleString(), centerX, centerY - 6);

    ctx.fillStyle = '#64748b';
    ctx.font = '600 10px Plus Jakarta Sans, sans-serif';
    ctx.fillText('RPM x 1000', centerX, centerY + 14);
  }
}
