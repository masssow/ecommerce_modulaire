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
    const summary = document.getElementById('order-summary');
    if (!summary) return;

    const subtotalCts = Number(summary.dataset.subtotalCents || 0); // en CENTIMES
    const taxRate = Number(summary.dataset.taxRate || 0);

    const elSubtotal = document.getElementById('js-subtotal');
    const elTva      = document.getElementById('js-tva');
    const elShip     = document.getElementById('js-shipping');
    const elGrand    = document.getElementById('js-grandtotal');
    const elShipNote = document.getElementById('js-ship-note');

    const radios = document.querySelectorAll('.js-shipping');

    const fmt = (cents) => (cents / 100).toLocaleString('fr-FR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });

    function recompute() {
      const checked = document.querySelector('.js-shipping:checked');
      let priceCts = 0, thresholdCts = 0;

      if (checked) {
        priceCts     = Number(checked.dataset.priceCents || 0);
        thresholdCts = Number(checked.dataset.freeThresholdCents || 0);
      }

      const shippingCts = (thresholdCts > 0 && subtotalCts >= thresholdCts) ? 0 : priceCts;
      const tvaCts      = Math.round(subtotalCts * taxRate / 100);
      const grandCts    = subtotalCts + tvaCts + shippingCts;

      elSubtotal.textContent = fmt(subtotalCts);
      elTva.textContent      = fmt(tvaCts);
      elShip.textContent     = fmt(shippingCts);
      elGrand.textContent    = fmt(grandCts);

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

    radios.forEach(r => r.addEventListener('change', recompute));
    recompute(); // init au chargement
  });