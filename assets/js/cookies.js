// assets/js/cookies.js

(function () {
  const banner = document.getElementById('cookie-banner');
  if (!banner) return;

  
  // Si déjà accepté → on cache
  if (localStorage.getItem('cookie_consent')) {
    banner.remove();
    return;
  }

  banner.querySelectorAll('[data-cookie-choice]').forEach(btn => {
    btn.addEventListener('click', function () {
      const choice = this.dataset.cookieChoice;

      localStorage.setItem('cookie_consent', choice);
      localStorage.setItem('cookie_consent_date', new Date().toISOString());

      // Disparition clean
      banner.style.transition = 'transform .3s ease, opacity .3s ease';
      banner.style.transform = 'translateY(100%)';
      banner.style.opacity = '0';

      setTimeout(() => banner.remove(), 300);

      // Hook futur analytics
      if (choice === 'all') {
        window.dispatchEvent(new Event('cookies:accepted'));
      }
    });
  });
})();
