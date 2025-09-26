// import './bootstrap.js';
// /*
//  * Welcome to your app's main JavaScript file!
//  *
//  * This file will be included onto the page via the importmap() Twig function,
//  * which should already be in your base.html.twig.
//  */
// import './styles/app.scss';

// import 'jquery';
// import 'bootstrap';
// import 'owl.carousel';

// import 'slick-carousel';
// import swal from 'sweetalert';
// import 'daterangepicker';
// import moment from 'moment'; // si tu souhaites manipuler les dates


// import GLightbox from 'glightbox';
// import 'magnific-popup';
// import 'select2';
// import './js/main.js';
// // import './js/map-custom.js';
// import './js/slick-custom.js';
// import Isotope from 'isotope-layout';



// window.$ = $;
// window.jQuery = $;
// window.Isotope = Isotope;

// console.log(typeof $.fn.isotope); 
// // Ratachement de Isotope à jQuery
// $.fn.isotope = function(options) {
//     return new Isotope(this[0], options);
// };

// // $(document).ready(function() {
// //     $(".owl-carousel").owlCarousel();
// // });

// // document.addEventListener('DOMContentLoaded', () => {
// //     const grid = document.querySelector('.grid');
// //     if (grid) {
// //         const iso = new Isotope(grid, {
// //             itemSelector: '.grid-item',
// //             layoutMode: 'fitRows'
// //         });
// //     }
// // });

// // Menu categories produits
// // document.addEventListener('DOMContentLoaded', () => {
// //     let filterButtons = document.querySelectorAll('.filter-tope-group button');

// //     filterButtons.forEach(button => {
// //         button.addEventListener('click', function () {
// //             // Retirer la classe "how-active1" de tous les boutons
// //             filterButtons.forEach(btn => btn.classList.remove('how-active1'));

// //             // Ajouter la classe "how-active1" au bouton cliqué
// //             this.classList.add('how-active1');
// //         });
// //     });
// // });


// // const lightbox = GLightbox({
// //     selector: '.glightbox'
// // });

// // Daterangepicker init
// // $(function () {
// //     $('input[name=\"daterange\"]').daterangepicker({
// //         opens: 'left'
// //     }, function(start, end, label) {
// //         console.log("New date range selected: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
// //     });
// // });

// // Magnific Popup init
// // $(document).ready(function() {
// //     $('.popup-link').magnificPopup({
// //         type: 'image'
// //     });
// // });

// console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');


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
