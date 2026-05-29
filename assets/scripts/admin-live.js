/**
 * Polls admin/staff live endpoints and swaps table rows / dashboard sections without a full reload.
 */
const POLL_MS = 5000;

function initAdminLive() {
  const roots = document.querySelectorAll('[data-admin-live-url]');
  if (!roots.length) {
    return;
  }

  roots.forEach((root) => {
    const url = root.getAttribute('data-admin-live-url');
    const targetSelector = root.getAttribute('data-admin-live-target');
    if (!url || !targetSelector) {
      return;
    }

    let etag = root.getAttribute('data-admin-live-etag') || '';
    let inFlight = false;

    const refresh = async () => {
      if (document.hidden || inFlight) {
        return;
      }
      inFlight = true;
      try {
        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (etag) {
          headers['If-None-Match'] = etag;
        }
        const res = await fetch(url, {
          headers,
          credentials: 'same-origin',
        });
        if (res.status === 304) {
          return;
        }
        if (!res.ok) {
          return;
        }
        const nextEtag = res.headers.get('X-Admin-Live');
        const html = await res.text();
        const target = document.querySelector(targetSelector);
        if (!target || !html) {
          return;
        }
        if (nextEtag && nextEtag === etag) {
          return;
        }
        target.innerHTML = html;
        if (nextEtag) {
          etag = nextEtag;
          root.setAttribute('data-admin-live-etag', etag);
        }
      } catch {
        // ignore transient network errors
      } finally {
        inFlight = false;
      }
    };

    refresh();
    window.setInterval(refresh, POLL_MS);
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAdminLive);
} else {
  initAdminLive();
}
