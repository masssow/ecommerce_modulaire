// cart.js — logique Panier (AJAX + badge + drawer + login redirect)

/* ===================== Config / Helpers ===================== */
const CART_ROUTES = window.routes || {};
const ADD_URL   = CART_ROUTES.add   || '/panier';         // POST
const PANEL_URL = CART_ROUTES.panel || '/panier/panel';   // GET (HTML)
const SHOW_URL  = CART_ROUTES.show  || '/panier';         // GET (JSON)

const LOGIN_URL = document.body.dataset.loginUrl || '/login';
const IS_AUTH   = (document.body.dataset.isAuth === '1' || document.body.dataset.isAuth === 'true');

const cartIcon  = document.getElementById('cart-icon');   // a .icon-header-noti
const cartBadge = document.getElementById('cart-badge');  // <span id="cart-badge">

function setBadge(qty) {
  const n = Math.max(0, Number(qty || 0));
  if (cartIcon)  cartIcon.setAttribute('data-notify', String(n));
  if (cartBadge) {
    cartBadge.textContent = String(n);
    if (n > 0) { cartBadge.classList.remove('d-none'); }
    else { cartBadge.classList.add('d-none'); }
  }
}
window.updateCartBadge = setBadge; // debug global

function ensureToastStyles() {
  if (document.getElementById('cart-toast-style')) return;
  const css = `
  .cart-toast{position:fixed;right:16px;top:16px;z-index:9999;display:flex;gap:8px;align-items:center;padding:10px 14px;border-radius:10px;box-shadow:0 8px 28px rgba(0,0,0,.15);background:#1f2937;color:#fff;font-size:14px;opacity:0;transform:translateY(-8px);transition:.25s ease}
  .cart-toast.show{opacity:1;transform:translateY(0)}
  .cart-toast .check{display:inline-flex;justify-content:center;align-items:center;width:22px;height:22px;border-radius:999px;background:#10b981}
  `;
  const el = document.createElement('style');
  el.id = 'cart-toast-style';
  el.textContent = css;
  document.head.appendChild(el);
}
function toastAdded(msg = 'Ajouté au panier') {
  ensureToastStyles();
  const t = document.createElement('div');
  t.className = 'cart-toast';
  t.innerHTML = `<span class="check">✓</span><span>${msg}</span>`;
  document.body.appendChild(t);
  requestAnimationFrame(() => t.classList.add('show'));
  setTimeout(() => {
    t.classList.remove('show');
    setTimeout(() => t.remove(), 250);
  }, 1600);
}

/* ===================== Drawer ===================== */
function attachCartDrawerEvents(scope = document) {
  scope.querySelectorAll('.js-hide-cart').forEach(el => {
    el.addEventListener('click', e => {
      e.preventDefault();
      document.querySelector('.js-panel-cart')?.classList.remove('show-header-cart');
    });
  });

  scope.querySelectorAll('.js-show-cart').forEach(el => {
    el.addEventListener('click', e => {
      e.preventDefault();
      document.querySelector('.js-panel-cart')?.classList.add('show-header-cart');
    });
  });

  scope.querySelectorAll('.s-full').forEach(el => {
    el.addEventListener('click', () => {
      document.querySelector('.js-panel-cart')?.classList.remove('show-header-cart');
    });
  });

  scope.querySelectorAll('.js-view-cart').forEach(el => {
    el.addEventListener('click', e => {
      e.preventDefault();
      document.querySelector('.js-panel-cart')?.classList.remove('show-header-cart');
      setTimeout(() => { window.location.href = el.getAttribute('href') || '/mon-panier'; }, 250);
    });
  });
}

async function reloadCartDrawer() {
  try {
    const res = await fetch(PANEL_URL, { credentials: 'same-origin' });
    if (!res.ok) throw new Error(await res.text());
    const data = await res.json();
    const drawerContainer = document.getElementById('cart-drawer');
    if (drawerContainer) {
      drawerContainer.innerHTML = data.html || '';
      attachCartDrawerEvents(drawerContainer);
    }
  } catch (err) {
    console.error('Erreur panel panier:', err);
  }
}
window.reloadCartDrawer = reloadCartDrawer; // debug global

async function refreshBadgeFromApi() {
  try {
    const res = await fetch(SHOW_URL, { credentials: 'same-origin' });
    if (!res.ok) throw new Error(await res.text());
    const data = await res.json();
    setBadge(data.total_qty || 0);
  } catch (err) {
    console.warn('Impossible de rafraîchir le badge:', err);
  }
}
window.refreshCartBadge = refreshBadgeFromApi; // debug global

/* ===================== Add to cart ===================== */
function getQtyFromButton(btn) {
  // Supporte data-qty-el="#qty" OU data-qty="2"
  const sel = btn.dataset.qtyEl;
  if (sel) {
    const el = document.querySelector(sel);
    if (el) {
      const v = parseInt(el.value, 10);
      return Number.isFinite(v) && v > 0 ? v : 1;
    }
  }
  const direct = parseInt(btn.dataset.qty || '1', 10);
  return Number.isFinite(direct) && direct > 0 ? direct : 1;
}

async function addToCart(variantId, qty = 1) {
  if (!variantId) {
    console.error('variantId manquant pour addToCart');
    return;
  }
  // if (!IS_AUTH) {
  //   const url = new URL(LOGIN_URL, window.location.origin);
  //   url.searchParams.set('redirect', window.location.href);
  //   window.location.assign(url.toString());
  //   return;
  // }

  try {
    const res = await fetch(ADD_URL, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ variant: String(variantId), qty: Number(qty) })
    });
    if (res.status === 401 || res.status === 403) {
      const url = new URL(LOGIN_URL, window.location.origin);
      url.searchParams.set('redirect', window.location.href);
      window.location.assign(url.toString());
      return;
    }
    
    if (!res.ok) {
      const txt = await res.text();
      console.error('Ajout panier KO:', txt);
      return;
    }

    const data = await res.json();          // { total, total_qty, items: [...] }
    setBadge(data.total_qty || 0);          // maj badge
    toastAdded('Produit ajouté au panier'); // toast
    reloadCartDrawer();                     // maj drawer
  } catch (err) {
    console.error('Erreur réseau addToCart:', err);
  }
}
window.addToCart = addToCart; // global pour appels inline si besoin

/* ===================== Init bindings ===================== */
function bindAddButtons(scope = document) {
  scope.querySelectorAll('.js-addcart-detail').forEach(btn => {
    const handler = (e) => {
      // Empêche toute navigation parasite (overlays <a>, etc.)
      e.preventDefault();
      e.stopPropagation();
      if (e.stopImmediatePropagation) e.stopImmediatePropagation();

      const variantId = btn.dataset.variantId;
      if (!variantId) {
        console.error('data-variant-id manquant sur le bouton', btn);
        return;
      }
      const qty = getQtyFromButton(btn);
      addToCart(variantId, qty);
      return false;
    };

    // Évite multiples ajouts si ré-initialisé
    btn.removeEventListener('click', handler);
    btn.addEventListener('click', handler);
    // Option: interdire clic milieu
    btn.addEventListener('auxclick', handler);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  attachCartDrawerEvents(document);
  bindAddButtons(document);

  // Rafraîchir badge au chargement
  refreshBadgeFromApi();

  // Charger le drawer si présent
  if (document.getElementById('cart-drawer')) {
    reloadCartDrawer();
  }
});



//====================================================================
