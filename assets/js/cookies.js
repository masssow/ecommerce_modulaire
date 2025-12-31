// assets/js/cookies.js
(function () {
  const KEY = 'kp_cookie_consent'; // "all" | "necessary"
  const banner = document.getElementById('cookie-banner');
  if (!banner) return;

  const btnAccept = document.getElementById('cookie-accept');
  const btnNecessary = document.getElementById('cookie-necessary');

  function getConsent() {
    return localStorage.getItem(KEY);
  }

  function setConsent(value) {
    localStorage.setItem(KEY, value);

    // Event interne pour que d'autres scripts puissent réagir
    window.dispatchEvent(new CustomEvent('cookie:consent', {
      detail: { consent: value }
    }));
  }

  function hide() { banner.style.display = 'none'; }
  function show() { banner.style.display = 'block'; }

  // Affiche si pas encore choisi
  if (!getConsent()) show();

  btnAccept?.addEventListener('click', () => {
    setConsent('all');
    hide();
  });

  btnNecessary?.addEventListener('click', () => {
    setConsent('necessary');
    hide();
  });
})();
