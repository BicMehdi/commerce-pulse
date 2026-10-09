"use strict";
document.querySelectorAll("[data-confirm]").forEach(button => {
  button.addEventListener("click", event => {
    if (!window.confirm(button.dataset.confirm)) event.preventDefault();
  });
});
const euros = value => new Intl.NumberFormat("fr-FR", { maximumFractionDigits: 0 }).format(value);
document.querySelectorAll("canvas[data-sales-chart]").forEach(canvas => {
  const data = JSON.parse(canvas.dataset.salesChart);
  const draw = () => {
    const rect = canvas.getBoundingClientRect(), dpr = window.devicePixelRatio || 1;
    canvas.width = Math.round(rect.width * dpr); canvas.height = Math.round(rect.height * dpr);
    const ctx = canvas.getContext("2d"); ctx.scale(dpr, dpr);
    const w = rect.width, h = rect.height, pad = { left: 58, right: 15, top: 15, bottom: 42 };
    const chartW = w - pad.left - pad.right, chartH = h - pad.top - pad.bottom;
    const max = Math.max(100, ...data.map(d => d.value_cents / 100));
    const ceiling = Math.ceil(max / 100) * 100;
    ctx.font = "11px Segoe UI, sans-serif"; ctx.textBaseline = "middle";
    for (let i = 0; i <= 4; i++) {
      const y = pad.top + chartH * (1 - i / 4);
      ctx.strokeStyle = "#e6ebe3"; ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(w-pad.right,y); ctx.stroke();
      ctx.fillStyle = "#728077"; ctx.textAlign = "right"; ctx.fillText(euros(ceiling * i / 4) + " €", pad.left - 10, y);
    }
    const step = chartW / data.length;
    data.forEach((day, i) => {
      const valueH = chartH * (day.value_cents / 100 / ceiling);
      ctx.fillStyle = "#527c64";
      ctx.fillRect(pad.left+i*step+step*.2,pad.top+chartH-valueH,Math.max(2,step*.6),valueH);
      if (i % Math.max(1, Math.ceil(data.length / 6)) === 0 || i === data.length-1) {
        ctx.fillStyle = "#728077"; ctx.textAlign = "center";
        ctx.fillText(day.date.slice(8,10)+"/"+day.date.slice(5,7),pad.left+(i+.5)*step,h-20);
      }
    });
    ctx.save(); ctx.translate(11,pad.top+chartH/2); ctx.rotate(-Math.PI/2); ctx.fillStyle="#728077"; ctx.textAlign="center"; ctx.fillText("Valeur (€)",0,0); ctx.restore();
  };
  const observer = new ResizeObserver(draw); observer.observe(canvas);
});
