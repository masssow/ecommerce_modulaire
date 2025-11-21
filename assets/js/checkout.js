// assets/js/checkout.js
document.addEventListener('DOMContentLoaded', () => {
  // --- Afficher / masquer formulaire d'adresse livraison
  const showShipBtn = document.getElementById('show-ship-form');
  const hideShipBtn = document.getElementById('hide-ship-form');
  const shipForm    = document.getElementById('add-shipping-form');

  showShipBtn?.addEventListener('click', () => shipForm?.classList.remove('d-none'));
  hideShipBtn?.addEventListener('click', () => shipForm?.classList.add('d-none'));

  // --- Facturation : même adresse ou autre
  const toggleBilling = document.getElementById('toggle-billing-choice');
  const billingChoice = document.getElementById('billing-choice');
  const billingSameIn = document.getElementById('billing-same');
  const billingUsing  = document.getElementById('billing-using-same');

  if (toggleBilling && billingChoice && billingSameIn && billingUsing) {
    toggleBilling.addEventListener('click', () => {
      const nowHidden = billingChoice.classList.toggle('d-none');
      if (nowHidden) {
        billingSameIn.value = '1';
        billingUsing.classList.remove('d-none');
      } else {
        billingSameIn.value = '0';
        billingUsing.classList.add('d-none');
      }
    });
  }

   // --- Recalcul dynamique des totaux (TVA + Port + Total)
  // IMPORTANT : ces montants sont UNIQUEMENT pour l'affichage.
  // Le backend recalcule toujours le total à partir du panier + shipping_method_id.
  const summary = document.getElementById('order-summary');
  if (!summary) return;

  // Données de base (CENTIMES, TVA en %)
  const subtotalCtsBase = Number(summary.dataset.subtotalCents || 0); // ex: 12345 pour 123,45 €
  const taxRate         = Number(summary.dataset.taxRate || 0);       // ex: 20

  // Éléments à mettre à jour
  const elSubtotal = document.getElementById('js-subtotal');
  const elTvaRate  = document.getElementById('os-tva-rate');
  const elTva      = document.getElementById('os-tva');
  const elShip     = document.getElementById('js-shipping');
  const elGrand    = document.getElementById('os-total');
  const elShipNote = document.getElementById('js-ship-note');

  // Radios expédition
  const radios = document.querySelectorAll('.js-shipping');

  // Format monétaire FR
  const fmtMoney = (cents) =>
    (Number(cents) / 100).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  // Format taux pour affichage
  const fmtRate = (rate) =>
    Number(rate).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  function recompute() {
    // Sous-total en centimes (si tu veux qu’il varie côté client, change ici)
    const subtotalCts = subtotalCtsBase;

    // Récup input radio sélectionné
    const checked = document.querySelector('.js-shipping:checked');

    let priceCts = 0;        // prix du port TTC en centimes
    let thresholdCts = 0;    // seuil “franco” en centimes

    if (checked) {
      priceCts     = Number(checked.dataset.priceCents || 0);
      thresholdCts = Number(checked.dataset.freeThresholdCents || 0);
    }

    // Calcul port (franco si seuil atteint)
    const shippingCts = (thresholdCts > 0 && subtotalCts >= thresholdCts) ? 0 : priceCts;

    // TVA sur les produits (si tu veux aussi taxer le port, ajoute + Math.round(shippingCts * taxRate / 100))
    const tvaCts   = Math.round(subtotalCts * taxRate / 100);
    const grandCts = subtotalCts + tvaCts + shippingCts;

    // MAJ UI
    if (elSubtotal) elSubtotal.textContent = fmtMoney(subtotalCts);
    if (elTvaRate)  elTvaRate.textContent  = fmtRate(taxRate);
    if (elTva)      elTva.textContent      = fmtMoney(tvaCts);
    if (elShip)     elShip.textContent     = fmtMoney(shippingCts);
    if (elGrand)    elGrand.textContent    = fmtMoney(grandCts) + ' €';

    if (elShipNote) {
      if (shippingCts === 0 && thresholdCts > 0) {
        elShipNote.textContent = '— Livraison offerte';
        elShipNote.classList.add('text-success');
      } else {
        elShipNote.textContent = '';
        elShipNote.classList.remove('text-success');
      }
    }
  }

  // Listeners sur les modes d’expédition
  radios.forEach(r => r.addEventListener('change', recompute));

  // Initialisation
  recompute();
});
