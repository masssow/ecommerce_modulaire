// assets/js/analytics.js
(function () {
  const KEY = 'kp_cookie_consent';

  function loadGtag(measurementId) {
    if (!measurementId) return;

    // évite double chargement
    if (window.__kpGtagLoaded) return;
    window.__kpGtagLoaded = true;

    const s = document.createElement('script');
    s.async = true;
    s.src = `https://www.googletagmanager.com/gtag/js?id=${measurementId}`;
    document.head.appendChild(s);

    window.dataLayer = window.dataLayer || [];
    function gtag(){ dataLayer.push(arguments); }
    window.gtag = gtag;

    gtag('js', new Date());
    gtag('config', measurementId);
  }

  function tryInit() {
    const consent = localStorage.getItem(KEY);
    if (consent !== 'all') return;

    // récupère l'ID GA depuis un data-attr dans <body> (simple et propre)
    const measurementId = document.body?.dataset?.gaId || '';
    loadGtag(measurementId);
  }

  // 1) au chargement
  document.addEventListener('DOMContentLoaded', tryInit);

  // 2) après clic sur cookie banner
  window.addEventListener('cookie:consent', (e) => {
    if (e.detail?.consent === 'all') tryInit();
  });
})();
