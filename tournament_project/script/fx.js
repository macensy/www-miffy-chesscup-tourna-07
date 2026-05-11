

(function () {
  /* ── 1. GLOWING CURSOR ─────────────────────────────────── */
  const cursorDot = document.createElement('div');
  cursorDot.id = 'fx-cursor-dot';
  document.body.appendChild(cursorDot);

  const cursorStyle = document.createElement('style');
  cursorStyle.textContent = `
    *, *::before, *::after { cursor: none !important; }
    #fx-cursor-dot {
      position: fixed; z-index: 99999; pointer-events: none;
      width: 10px; height: 10px; border-radius: 50%;
      background: radial-gradient(circle, #F5C98A, #D4824A);
      box-shadow: 0 0 10px 3px rgba(212,130,74,0.7), 0 0 22px 8px rgba(212,130,74,0.25);
      transform: translate(-50%, -50%);
      transition: width 0.12s, height 0.12s;
      top: 0; left: 0;
    }
    #fx-cursor-dot.clicking { width: 14px; height: 14px; background: #F5C98A; }
    #fx-cursor-dot.hovering { width: 14px; height: 14px; box-shadow: 0 0 14px 5px rgba(212,130,74,0.85), 0 0 30px 10px rgba(212,130,74,0.3); }
  `;
  document.head.appendChild(cursorStyle);

  document.addEventListener('mousemove', e => {
    cursorDot.style.left = e.clientX + 'px';
    cursorDot.style.top  = e.clientY + 'px';
  });
  document.addEventListener('mousedown', () => cursorDot.classList.add('clicking'));
  document.addEventListener('mouseup',   () => cursorDot.classList.remove('clicking'));

  document.addEventListener('mouseover', e => {
    const isInteractive = ['A','BUTTON','INPUT','SELECT','LABEL'].includes(e.target.tagName) ||
                          e.target.closest('button, a, [onclick], .btn-login, .btn-register, .btn-admin, .mode-btn, .round-pill, .btn-save, .btn-generate, .btn-reset, .tab-btn');
    cursorDot.classList.toggle('hovering', !!isInteractive);
  });


  /* ── 2. BUTTON RIPPLE ──────────────────────────────────── */
  document.addEventListener('click', e => {
    const btn = e.target.closest('button, .btn-login, .btn-register, .btn-admin, .btn-generate, .btn-reset, .round-pill, .tab-btn, .mode-btn');
    if (!btn) return;
    const rect   = btn.getBoundingClientRect();
    const ripple = document.createElement('span');
    const size   = Math.max(rect.width, rect.height) * 1.6;
    ripple.style.cssText = `
      position:absolute; border-radius:50%; pointer-events:none;
      width:${size}px; height:${size}px;
      left:${e.clientX - rect.left - size/2}px;
      top:${e.clientY  - rect.top  - size/2}px;
      background:rgba(245,201,138,0.22);
      transform:scale(0); animation:fxRipple 0.55s ease-out forwards;
      z-index:9998;
    `;
    const prevPos = getComputedStyle(btn).position;
    if (prevPos === 'static') btn.style.position = 'relative';
    btn.style.overflow = 'hidden';
    btn.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
  });

  const rippleStyle = document.createElement('style');
  rippleStyle.textContent = `@keyframes fxRipple { to { transform:scale(1); opacity:0; } }`;
  document.head.appendChild(rippleStyle);


  /* ── 3. INPUT FOCUS GLOW ───────────────────────────────── */
  document.querySelectorAll('input.field-input, select.field-select').forEach(el => {
    el.addEventListener('focus', () => {
      el.style.boxShadow = '0 0 0 3px rgba(212,130,74,0.18), 0 0 16px rgba(212,130,74,0.12)';
      el.style.borderColor = '#E8A96A';
    });
    el.addEventListener('blur', () => {
      el.style.boxShadow = '';
      el.style.borderColor = '';
    });
  });


  /* ── 4. CARD TILT (glass-card elements) ────────────────── */
  document.querySelectorAll('.glass-card, .stat-card, .login-card, .admin-card, .reg-wrap').forEach(card => {
    card.addEventListener('mousemove', e => {
      const r    = card.getBoundingClientRect();
      const cx   = r.left + r.width  / 2;
      const cy   = r.top  + r.height / 2;
      const dx   = (e.clientX - cx) / (r.width  / 2);
      const dy   = (e.clientY - cy) / (r.height / 2);
      const tiltX = dy * -4;
      const tiltY = dx * 4;
      card.style.transform    = `perspective(900px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(1.008)`;
      card.style.transition   = 'transform 0.08s ease';
      card.style.willChange   = 'transform';
    });
    card.addEventListener('mouseleave', () => {
      card.style.transform  = '';
      card.style.transition = 'transform 0.4s ease';
    });
  });


  /* ── 5. SCROLL REVEAL ──────────────────────────────────── */
  const revealStyle = document.createElement('style');
  revealStyle.textContent = `
    .fx-reveal { opacity:0; transform:translateY(28px); transition:opacity 0.55s ease, transform 0.55s ease; }
    .fx-reveal.visible { opacity:1; transform:translateY(0); }
  `;
  document.head.appendChild(revealStyle);

  function initReveal() {
    document.querySelectorAll('.glass-card, .stat-card, .info-card').forEach((el, i) => {
      el.classList.add('fx-reveal');
      el.style.transitionDelay = (i * 0.07) + 's';
    });
    const obs = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fx-reveal').forEach(el => obs.observe(el));
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initReveal);
  else initReveal();


  /* ── 6. ANIMATED BACKGROUND CANVAS (if not already present) ── */
  if (!document.getElementById('bgCanvas')) {
    const canvas = document.createElement('canvas');
    canvas.id = 'bgCanvas';
    canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:-1;pointer-events:none;';
    document.body.insertBefore(canvas, document.body.firstChild);

    const bgStyle = document.createElement('style');
    bgStyle.textContent = `body { background: #0a0301 !important; }`;
    document.head.appendChild(bgStyle);

    const ctx    = canvas.getContext('2d');
    const pieces = ['♟','♜','♞','♝','♛','♚'];
    let particles = [], W, H, time = 0;

    function resize() { W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
    resize(); window.addEventListener('resize', resize);

    for (let i = 0; i < 22; i++) particles.push({
      x: Math.random() * 1400, y: Math.random() * 900,
      symbol: pieces[Math.floor(Math.random() * 6)],
      size: 13 + Math.random() * 24, speed: 0.12 + Math.random() * 0.26,
      drift: (Math.random() - 0.5) * 0.18, alpha: 0.025 + Math.random() * 0.06,
      rot: Math.random() * Math.PI * 2, rotSpeed: (Math.random() - 0.5) * 0.005,
      wave: Math.random() * Math.PI * 2, waveAmp: 0.3 + Math.random() * 0.5
    });

    function drawBg() {
      time += 0.004;
      const g = ctx.createRadialGradient(W*.5,H*.4,0,W*.5,H*.4,W*.85);
      g.addColorStop(0,'rgba(38,17,7,.97)'); g.addColorStop(.55,'rgba(20,8,3,.98)'); g.addColorStop(1,'rgba(10,3,1,1)');
      ctx.fillStyle = g; ctx.fillRect(0,0,W,H);
      [{x:W*(.2+.08*Math.sin(time*.7)),y:H*(.25+.07*Math.cos(time*.5)),r:W*.3,c:`rgba(107,58,42,${.05+.02*Math.sin(time)})`},
       {x:W*(.82+.06*Math.cos(time*.6)),y:H*(.65+.08*Math.sin(time*.4)),r:W*.25,c:`rgba(181,98,42,${.045+.015*Math.cos(time*1.2)})`},
       {x:W*(.5+.05*Math.sin(time*.9)),y:H*(.9+.04*Math.cos(time*.8)),r:W*.2,c:`rgba(212,130,74,${.035+.01*Math.sin(time*1.5)})`}
      ].forEach(o => {
        const og = ctx.createRadialGradient(o.x,o.y,0,o.x,o.y,o.r);
        og.addColorStop(0,o.c); og.addColorStop(1,'transparent');
        ctx.fillStyle=og; ctx.fillRect(0,0,W,H);
      });
      ctx.textBaseline='middle';
      particles.forEach(p => {
        p.wave += 0.018;
        ctx.save();
        ctx.translate(p.x%W, p.y%H); ctx.rotate(p.rot);
        ctx.globalAlpha = p.alpha * (0.7 + 0.3*Math.sin(p.wave));
        ctx.fillStyle='#E8A96A'; ctx.font=`${p.size}px serif`;
        ctx.fillText(p.symbol,0,0); ctx.restore();
        p.y -= p.speed; p.x += p.drift + Math.sin(p.wave)*p.waveAmp; p.rot += p.rotSpeed;
        if (p.y < -60) { p.y = H+60; p.x = Math.random()*W; }
        if (p.x < -60) p.x = W+60; if (p.x > W+60) p.x = -60;
      });
      requestAnimationFrame(drawBg);
    }
    drawBg();
  }

})();
