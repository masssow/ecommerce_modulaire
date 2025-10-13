
import './bootstrap.js';
import './styles/app.scss';



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

// console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// ==============================
// ✅ Attache globale
// ==============================
window.$ = $;
window.jQuery = $;
window.Isotope = Isotope;

// ➜ Attacher Isotope à jQuery pour ton thème
$.fn.isotope = function(options) {
    return new Isotope(this[0], options);
};

// ==============================
// ✅ Import de tes scripts custom
// ==============================
import './js/main.js';
import './js/slick-custom.js';
// import './js/map-custom.js'; // décommenter si nécessaire

// ==============================
// ✅ Initialisations optionnelles
// ==============================

// GLightbox
const lightbox = GLightbox({
    selector: '.glightbox'
});

// Owl Carousel
$(document).ready(function() {
    $(".owl-carousel").owlCarousel();
});

// Daterangepicker
$(function () {
    $('input[name="daterange"]').daterangepicker({
        opens: 'left'
    }, function(start, end, label) {
        console.log("New date range selected: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
    });
});

// Magnific Popup
$(document).ready(function() {
    $('.popup-link').magnificPopup({
        type: 'image'
    });
});

// ==============================
// ✅ Debug
// ==============================
console.log('✅ app.js chargé, jQuery version:', $.fn.jquery);
console.log('✅ Isotope attaché à jQuery :', typeof $.fn.isotope);
console.log('🎉 Welcome to your modular Symfony E-commerce frontend!');
