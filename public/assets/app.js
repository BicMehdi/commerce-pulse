"use strict";
document.documentElement.classList.add("js-enabled");

/* Progressive enhancement: no motion or JavaScript is required to shop. */
const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
const finePointer = window.matchMedia("(hover: hover) and (pointer: fine)");
const revealTargets = [...document.querySelectorAll("[data-reveal]")];
if (!reducedMotion.matches && "IntersectionObserver" in window) {
  document.documentElement.classList.add("motion-ready");
  const revealObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add("is-visible");
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.08 });
  revealTargets.forEach(element => revealObserver.observe(element));
}
reducedMotion.addEventListener("change", event => {
  if (event.matches) {
    document.documentElement.classList.remove("motion-ready");
    revealTargets.forEach(element => element.classList.add("is-visible"));
    document.querySelectorAll("[data-depth]").forEach(element => {
      element.style.removeProperty("--shift-x");
      element.style.removeProperty("--shift-y");
    });
  }
});

let scrollFrame = null;
const updateProgress = () => {
  const length = document.documentElement.scrollHeight - window.innerHeight;
  document.documentElement.style.setProperty("--progress", String(length > 0 ? Math.min(1, window.scrollY / length) : 0));
  scrollFrame = null;
};
window.addEventListener("scroll", () => {
  if (scrollFrame === null) scrollFrame = requestAnimationFrame(updateProgress);
}, { passive: true });
window.addEventListener("resize", updateProgress);
updateProgress();

document.querySelectorAll("[data-scene]").forEach(scene => {
  const objects = [...scene.querySelectorAll("[data-depth]")];
  let frame = null;
  scene.addEventListener("pointermove", event => {
    if (reducedMotion.matches || !finePointer.matches) return;
    if (frame !== null) cancelAnimationFrame(frame);
    frame = requestAnimationFrame(() => {
      const box = scene.getBoundingClientRect();
      const x = (event.clientX - box.left) / box.width - 0.5;
      const y = (event.clientY - box.top) / box.height - 0.5;
      objects.forEach(object => {
        const depth = Number(object.dataset.depth);
        object.style.setProperty("--shift-x", (x * depth * 22).toFixed(2) + "px");
        object.style.setProperty("--shift-y", (y * depth * 16).toFixed(2) + "px");
      });
      frame = null;
    });
  });
  scene.addEventListener("pointerleave", () => {
    if (frame !== null) cancelAnimationFrame(frame);
    frame = null;
    objects.forEach(object => {
      object.style.setProperty("--shift-x", "0px");
      object.style.setProperty("--shift-y", "0px");
    });
  });
});

document.querySelectorAll("[data-tilt]").forEach(card => {
  card.addEventListener("pointermove", event => {
    if (reducedMotion.matches || !finePointer.matches) return;
    const box = card.getBoundingClientRect();
    card.style.setProperty("--tilt-x", ((0.5 - (event.clientY - box.top) / box.height) * 5).toFixed(2) + "deg");
    card.style.setProperty("--tilt-y", (((event.clientX - box.left) / box.width - 0.5) * 5).toFixed(2) + "deg");
  });
  card.addEventListener("pointerleave", () => {
    card.style.setProperty("--tilt-x", "0deg");
    card.style.setProperty("--tilt-y", "0deg");
  });
});
document.querySelectorAll("[data-magnetic]").forEach(button => {
  button.addEventListener("pointermove", event => {
    if (reducedMotion.matches || !finePointer.matches) return;
    const box = button.getBoundingClientRect();
    button.style.transform = "translate(" + ((event.clientX - box.left - box.width / 2) * .045) + "px," + ((event.clientY - box.top - box.height / 2) * .045) + "px)";
  });
  button.addEventListener("pointerleave", () => { button.style.transform = ""; });
});
document.querySelectorAll(".button").forEach(button => {
  button.addEventListener("click", event => {
    if (reducedMotion.matches || typeof button.animate !== "function") return;
    const box = button.getBoundingClientRect();
    const ripple = document.createElement("span");
    ripple.className = "button-ripple";
    ripple.setAttribute("aria-hidden", "true");
    ripple.style.left = (event.detail ? event.clientX - box.left : box.width / 2) + "px";
    ripple.style.top = (event.detail ? event.clientY - box.top : box.height / 2) + "px";
    button.append(ripple);
    ripple.animate([{ transform: "scale(0)", opacity: .2 }, { transform: "scale(28)", opacity: 0 }], { duration: 550, easing: "ease-out" }).finished.catch(() => {}).finally(() => ripple.remove());
  });
});

document.querySelectorAll("[data-password-target]").forEach(button => {
  const input = document.getElementById(button.dataset.passwordTarget);
  if (!input) return;
  const originalLabel = button.getAttribute("aria-label");
  button.addEventListener("click", () => {
    const show = input.type === "password";
    input.type = show ? "text" : "password";
    button.textContent = show ? "MASQUER" : "VOIR";
    button.setAttribute("aria-pressed", String(show));
    button.setAttribute("aria-label", show ? originalLabel.replace("Afficher", "Masquer") : originalLabel);
  });
});
document.querySelectorAll("[data-confirm]").forEach(button => {
  button.addEventListener("click", event => {
    if (!window.confirm(button.dataset.confirm)) event.preventDefault();
  });
});
document.querySelectorAll('form[method="post"]').forEach(form => {
  form.addEventListener("submit", () => {
    form.querySelectorAll('button[type="submit"], button:not([type])').forEach(button => {
      if (!button.disabled) {
        button.dataset.submitting = "true";
        button.disabled = true;
        button.setAttribute("aria-busy", "true");
      }
    });
  });
});
window.addEventListener("pageshow", () => {
  document.querySelectorAll("[data-submitting]").forEach(button => {
    button.disabled = false;
    button.removeAttribute("aria-busy");
    delete button.dataset.submitting;
  });
});

/* The sales chart uses the same ink/paper/cobalt palette as the studio. */
const euros = value => new Intl.NumberFormat("fr-FR", { maximumFractionDigits: 0 }).format(value);
document.querySelectorAll("canvas[data-sales-chart]").forEach(canvas => {
  const data = JSON.parse(canvas.dataset.salesChart);
  const tooltip = document.createElement("div");
  tooltip.className = "chart-tooltip";
  tooltip.hidden = true;
  tooltip.setAttribute("aria-hidden", "true");
  canvas.parentElement.append(tooltip);
  let hovered = -1;
  let growth = reducedMotion.matches ? 1 : 0;
  let layout = null;
  const draw = () => {
    const rect = canvas.getBoundingClientRect();
    if (!rect.width || !rect.height) return;
    const dpr = window.devicePixelRatio || 1;
    canvas.width = Math.round(rect.width * dpr);
    canvas.height = Math.round(rect.height * dpr);
    const ctx = canvas.getContext("2d");
    if (!ctx) return;
    ctx.scale(dpr, dpr);
    const w = rect.width, h = rect.height, pad = { left: 57, right: 15, top: 15, bottom: 42 };
    const chartW = w - pad.left - pad.right, chartH = h - pad.top - pad.bottom;
    const ceiling = Math.ceil(Math.max(100, ...data.map(day => day.value_cents / 100)) / 100) * 100;
    ctx.font = "10px Courier New, monospace";
    ctx.textBaseline = "middle";
    for (let i = 0; i <= 4; i++) {
      const y = pad.top + chartH * (1 - i / 4);
      ctx.strokeStyle = "#d6d7cb";
      ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(w - pad.right, y); ctx.stroke();
      ctx.fillStyle = "#686b60"; ctx.textAlign = "right";
      ctx.fillText(euros(ceiling * i / 4) + " €", pad.left - 9, y);
    }
    const step = chartW / data.length;
    layout = { pad, step, chartW };
    data.forEach((day, i) => {
      const valueH = chartH * (day.value_cents / 100 / ceiling) * growth;
      ctx.fillStyle = hovered === i ? "#262823" : "#3455dc";
      ctx.fillRect(pad.left + i * step + step * .2, pad.top + chartH - valueH, Math.max(2, step * .6), valueH);
      if (i % Math.max(1, Math.ceil(data.length / 6)) === 0 || i === data.length - 1) {
        ctx.fillStyle = "#686b60"; ctx.textAlign = "center";
        ctx.fillText(day.date.slice(8, 10) + "/" + day.date.slice(5, 7), pad.left + (i + .5) * step, h - 20);
      }
    });
    ctx.save(); ctx.translate(10, pad.top + chartH / 2); ctx.rotate(-Math.PI / 2);
    ctx.fillStyle = "#686b60"; ctx.textAlign = "center"; ctx.fillText("Valeur (€)", 0, 0); ctx.restore();
  };
  let animation = null;
  const animateBars = () => {
    const start = performance.now();
    const frame = now => {
      const elapsed = Math.min(1, (now - start) / 700);
      growth = reducedMotion.matches ? 1 : 1 - Math.pow(1 - elapsed, 3);
      draw();
      if (elapsed < 1 && !reducedMotion.matches) animation = requestAnimationFrame(frame);
    };
    animation = requestAnimationFrame(frame);
  };
  if ("ResizeObserver" in window) new ResizeObserver(draw).observe(canvas);
  else window.addEventListener("resize", draw);
  if (!reducedMotion.matches && "IntersectionObserver" in window) {
    const observer = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting) { animateBars(); observer.disconnect(); }
    }, { threshold: .15 });
    observer.observe(canvas);
  } else { growth = 1; draw(); }
  reducedMotion.addEventListener("change", event => {
    if (event.matches) { if (animation) cancelAnimationFrame(animation); growth = 1; draw(); }
  });
  canvas.addEventListener("pointermove", event => {
    if (!layout) return;
    const rect = canvas.getBoundingClientRect();
    const index = Math.floor((event.clientX - rect.left - layout.pad.left) / layout.step);
    if (index < 0 || index >= data.length) { tooltip.hidden = true; hovered = -1; draw(); return; }
    hovered = index;
    const day = data[index];
    tooltip.textContent = day.date.slice(8, 10) + "/" + day.date.slice(5, 7) + " · " + euros(day.value_cents / 100) + " € · " + day.orders + " commande" + (day.orders === 1 ? "" : "s");
    const panel = canvas.parentElement.getBoundingClientRect();
    tooltip.hidden = false;
    const left = Math.max(12, Math.min(event.clientX - panel.left - tooltip.offsetWidth / 2, panel.width - tooltip.offsetWidth - 12));
    tooltip.style.left = left + "px";
    tooltip.style.top = Math.max(10, rect.top - panel.top + 18) + "px";
    draw();
  });
  canvas.addEventListener("pointerleave", () => { tooltip.hidden = true; hovered = -1; draw(); });
});
