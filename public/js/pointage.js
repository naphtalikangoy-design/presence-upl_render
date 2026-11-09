(() => {
  const csrf = document.querySelector('meta[name=csrf-token]').content;
  const btn = document.getElementById('btn'), msg = document.getElementById('msg'), status = document.getElementById('status');
  let nonce = null, busy = false, type = 'arrivee';
  setInterval(() => document.getElementById('clock').textContent = new Date().toLocaleString('fr-FR'), 1000);

  const getPos = () => new Promise(res => {
    if (!navigator.geolocation) return res(null);
    navigator.geolocation.getCurrentPosition(
      p => res({ latitude: p.coords.latitude, longitude: p.coords.longitude, accuracy: p.coords.accuracy, timestamp: p.timestamp }),
      () => res(null), { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
  });
  const post = (url, body) => fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) }).then(r => r.json());
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const setMode = t => { type = t; btn.textContent = t === 'depart' ? 'ENREGISTRER MON DÉPART' : 'EFFECTUER MON POINTAGE'; };
  const paint = d => {
    document.querySelectorAll('[data-k]').forEach(el => { const v = d.checks && d.checks[el.dataset.k]; el.className = 'pill ' + (v ? 'ok' : 'ko'); el.textContent = v ? 'OK' : 'Non'; });
    status.innerHTML = (d.demo ? '<span class="pill wait">SIMULATION</span> ' : '') + esc(d.label || '');
  };

  async function verify() {
    if (busy) return; busy = true;
    try {
      const d = await post('/stagiaire/pointage/verifier', { type, position: await getPos() });
      if (d.checks) paint(d);
      if (d.statut === 'POINTAGE_DEJA_EFFECTUE' && type === 'arrivee') { busy = false; setMode('depart'); return verify(); }
      nonce = d.ok ? d.nonce : null; btn.disabled = !d.ok;   // le serveur reste seul décideur au pointage
      msg.innerHTML = (!d.ok && d.message) ? '<div class="alert alert-secondary">' + esc(d.message) + '</div>' : '';
    } catch (e) { msg.innerHTML = '<div class="alert alert-danger">Erreur réseau.</div>'; }
    busy = false;
  }
  btn.addEventListener('click', async () => {
    btn.disabled = true; busy = true;
    const d = await post('/stagiaire/pointage/pointer', { type, position: await getPos(), nonce });
    busy = false; paint(d);
    msg.innerHTML = '<div class="alert alert-' + (d.ok ? 'success' : 'danger') + '">' + esc(d.message || 'Erreur') + '</div>';
    if (d.ok) setTimeout(() => { setMode('depart'); verify(); }, 2500); else setTimeout(verify, 1500);
  });
  verify(); setInterval(() => { if (!btn.disabled || !nonce) verify(); }, 15000);
})();
