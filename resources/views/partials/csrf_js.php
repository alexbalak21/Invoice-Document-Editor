<?php /** Defines BASE and makes every fetch() POST carry the CSRF token (retrying once if it went stale). */ ?>
<meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
<script>
const BASE = <?= json_encode(BASE_URL) ?>;
(function () {
  const meta        = document.querySelector('meta[name="csrf-token"]');
  const nativeFetch = window.fetch.bind(window);

  async function refreshToken() {
    try {
      const r = await nativeFetch(BASE + '/api/csrf', { credentials: 'same-origin' });
      if (!r.ok) return false;
      meta.content = (await r.json()).token;
      return true;
    } catch (e) { return false; }
  }

  function showSessionBanner() {
    if (document.getElementById('session-banner')) return;
    const b = document.createElement('div');
    b.id = 'session-banner';
    b.className = 'session-banner';
    b.innerHTML = 'Your session has expired. <a href="' + BASE + '/login" target="_blank" rel="noopener">Sign in again in a new tab</a>, then retry — your work on this page is kept.';
    document.body.appendChild(b);
  }

  window.fetch = async function (input, init) {
    init = init || {};
    const method = (init.method || (input && input.method) || 'GET').toUpperCase();
    if (method === 'GET' || method === 'HEAD') return nativeFetch(input, init);

    const send = () => {
      const headers = new Headers(init.headers || {});
      headers.set('X-CSRF-Token', meta.content);
      return nativeFetch(input, Object.assign({}, init, { headers, credentials: 'same-origin' }));
    };

    let res = await send();
    if (res.status === 419 && await refreshToken()) res = await send();   // token went stale (e.g. re-login in another tab)
    if (res.status === 401) showSessionBanner();
    return res;
  };
})();
</script>
