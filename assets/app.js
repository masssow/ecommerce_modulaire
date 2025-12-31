// assets/app.js
import './bootstrap.js';
import './styles/app.scss';
import './js/cookies.js';


// ==============================
// ✅ Dépendances JS principales
// ==============================
import $ from 'jquery';
import 'bootstrap';
import 'owl.carousel';
import 'slick-carousel';
import swal from 'sweetalert';
import 'daterangepicker';
import moment from 'moment';
import GLightbox from 'glightbox';
import 'magnific-popup';
import 'select2';
import Isotope from 'isotope-layout';
import imagesLoaded from 'imagesloaded';               // 🆕 important
import 'isotope-layout/js/layout-modes/fit-rows';      // 🆕 si tu utilises fitRows

// ==============================
// ✅ Attache globale
// ==============================
window.$ = $;
window.jQuery = $;
window.Isotope = Isotope;

// ➜ Wrapper jQuery **complet** pour Isotope (init + appels méthode)
$.fn.isotope = function (optsOrMethod, ...args) {
  return this.each(function () {
    let inst = $.data(this, 'isotopeInstance');
    if (!inst) {
      inst = new Isotope(this, optsOrMethod || {});
      $.data(this, 'isotopeInstance', inst);
    } else if (typeof optsOrMethod === 'string' && typeof inst[optsOrMethod] === 'function') {
      inst[optsOrMethod](...args);
    }
  });
};

// ==============================
// ✅ Import de tes scripts custom
// ==============================
import './js/main.js';
import './js/cart.js';
import './js/checkout.js';
import './js/slick-custom.js';
// import './js/map-custom.js'; // décommenter si nécessaire

// ==============================
// ✅ Initialisations UI diverses
// ==============================

// GLightbox
GLightbox({ selector: '.glightbox' });

// Owl Carousel
$(function () {
  $('.owl-carousel').owlCarousel();
});

// Daterangepicker
$(function () {
  $('input[name="daterange"]').daterangepicker({
    opens: 'left'
  }, function (start, end) {
    console.log('New date range selected:', start.format('YYYY-MM-DD'), 'to', end.format('YYYY-MM-DD'));
  });
});

// Magnific Popup
$(function () {
  $('.popup-link').magnificPopup({ type: 'image' });
});

// ==============================
// ✅ Isotope — après chargement des images
// ==============================
$(function () {
  const $grid = $('.isotope-grid');
  if (!$grid.length) return;

  // 1) Init
  $grid.isotope({
    itemSelector: '.isotope-item',
    layoutMode: 'fitRows',
    percentPosition: true
  });

  // Récupère l’instance stockée par le wrapper
  const iso = $grid.data('isotopeInstance');

  // 2) Relayout au fil du chargement des images
  imagesLoaded($grid.get(0)).on('progress', () => iso.layout());

  // 3) Filtres
  $('.filter-tope-group').on('click', 'button', function () {
    const filterValue = $(this).attr('data-filter') || '*';
    iso.arrange({ filter: filterValue });
    $('.filter-tope-group .how-active1').removeClass('how-active1');
    $(this).addClass('how-active1');
    setTimeout(() => iso.layout(), 50);
  });

  // 4) Filets de sécurité
  $(window).on('load resize', () => iso.layout());
  setTimeout(() => iso.layout(), 200);
});

// ==============================
// ✅ Debug
// ==============================
console.log('✅ app.js chargé, jQuery:', $.fn.jquery);
console.log('✅ Isotope wrapper:', typeof $.fn.isotope);
