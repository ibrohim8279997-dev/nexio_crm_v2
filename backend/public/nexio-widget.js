/*! Nexio CRM lead-capture widget.
 * <script src="https://API_HOST/nexio-widget.js" data-key="lfk_..." async></script>
 * Optional: <div id="nexio-form"></div> as the mount point (else rendered after the script tag).
 * data-lang="uz|ru|en" (default uz). Runs inside a shadow root so page CSS cannot break it.
 */
(function () {
  var s = document.currentScript || document.querySelector('script[data-key][src*="nexio-widget"]');
  if (!s) return;
  var key = s.getAttribute('data-key');
  if (!key) return;
  var base = new URL(s.src).origin + '/api/public/forms/' + encodeURIComponent(key);
  var T = {
    uz: { name: 'Ismingiz', phone: 'Telefon', email: 'Email', msg: 'Xabar', send: 'Yuborish', err: 'Xatolik, qayta urinib ko‘ring', need: 'Ism va telefon yoki email kiriting' },
    ru: { name: 'Ваше имя', phone: 'Телефон', email: 'Email', msg: 'Сообщение', send: 'Отправить', err: 'Ошибка, попробуйте ещё раз', need: 'Укажите имя и телефон или email' },
    en: { name: 'Your name', phone: 'Phone', email: 'Email', msg: 'Message', send: 'Send', err: 'Something went wrong, try again', need: 'Enter your name and a phone or email' }
  };
  var t = T[s.getAttribute('data-lang')] || T.uz;
  var host = document.getElementById('nexio-form');
  if (!host) { host = document.createElement('div'); s.parentNode.insertBefore(host, s.nextSibling); }
  var root = host.attachShadow ? host.attachShadow({ mode: 'open' }) : host;

  root.innerHTML =
    '<style>' +
    ':host{all:initial}form{font:14px/1.4 system-ui,sans-serif;display:grid;gap:10px;max-width:420px}' +
    'input,textarea{font:inherit;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;width:100%;box-sizing:border-box}' +
    'input:focus,textarea:focus{outline:2px solid #2563EB;border-color:transparent}' +
    'button{font:inherit;font-weight:600;padding:10px 14px;border:0;border-radius:8px;background:#2563EB;color:#fff;cursor:pointer}' +
    'button:hover{background:#1D4ED8}button:disabled{opacity:.6;cursor:default}' +
    '.m{padding:10px 12px;border-radius:8px;font:14px system-ui,sans-serif}.ok{background:#dcfce7;color:#166534}.er{background:#fee2e2;color:#991b1b}' +
    '.hp{position:absolute;left:-9999px;opacity:0}' +
    '</style>' +
    '<form novalidate>' +
    '<input name="name" placeholder="' + t.name + '" autocomplete="name" maxlength="160" required>' +
    '<input name="phone" placeholder="' + t.phone + '" autocomplete="tel" maxlength="40">' +
    '<input name="email" type="email" placeholder="' + t.email + '" autocomplete="email" maxlength="190">' +
    '<textarea name="message" rows="3" placeholder="' + t.msg + '" maxlength="2000"></textarea>' +
    '<input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">' +
    '<button type="submit">' + t.send + '</button><div class="m" hidden></div></form>';

  var form = root.querySelector('form'), btn = root.querySelector('button'), msg = root.querySelector('.m');
  function show(text, ok) { msg.textContent = text; msg.className = 'm ' + (ok ? 'ok' : 'er'); msg.hidden = false; }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var d = {}; ['name', 'phone', 'email', 'message', 'website'].forEach(function (k) { d[k] = form.elements[k].value.trim(); });
    if (!d.name || (!d.phone && !d.email)) return show(t.need, false);
    btn.disabled = true;
    fetch(base + '/submit', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(d) })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (x) {
        if (!x.ok) { btn.disabled = false; return show((x.j && x.j.error && x.j.error.message) || t.err, false); }
        form.reset(); show((x.j && x.j.message) || 'OK', true);
        if (x.j && x.j.redirect_url && /^https?:\/\//i.test(x.j.redirect_url)) setTimeout(function () { location.href = x.j.redirect_url; }, 1200);
        btn.disabled = false;
      })
      .catch(function () { btn.disabled = false; show(t.err, false); });
  });
})();
