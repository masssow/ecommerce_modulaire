import $ from 'jquery';
import 'animsition';

// ===========================================================
//  Gestion du PANIER (AJAX + Drawer)
// ===========================================================
const badge = document.getElementById('cart-badge');
const userId = document.body.dataset.userId || null;

/* -------------------- Badge -------------------- */
function toggleBadge(qty) {
  if (!badge) return;
  if (qty > 0) {
    badge.textContent = qty;
    badge.classList.remove('d-none');
  } else {
    badge.classList.add('d-none');
  }
}
window.toggleBadge = toggleBadge;

/* -------------------- Drawer : ouvrir / fermer -------------------- */
function attachCartDrawerEvents() {
  // Bouton "X"
  document.querySelectorAll('.js-hide-cart').forEach(el => {
    el.addEventListener('click', () => {
      document.querySelector('.js-panel-cart')?.classList.remove('show-header-cart');
    });
  });

  // Icône panier
  document.querySelectorAll('.js-show-cart').forEach(el => {
    el.addEventListener('click', () => {
      document.querySelector('.js-panel-cart')?.classList.add('show-header-cart');
    });
  });

  // Clic en dehors (overlay sombre)
  document.querySelectorAll('.s-full').forEach(el => {
    el.addEventListener('click', () => {
      document.querySelector('.js-panel-cart')?.classList.remove('show-header-cart');
    });
  });

   // 🆕 Ajoute redirection "Voir le panier"
  document.querySelectorAll('.js-view-cart').forEach(el => {
    el.addEventListener('click', e => {
      e.preventDefault();
      document.querySelector('.js-panel-cart')?.classList.remove('show-header-cart');

      // Rediriger après une courte pause pour la fermeture de l'animation
      setTimeout(() => {
        window.location.href = el.getAttribute('href');
      }, 300); //ajuster le délai
    });
  });
}

/* -------------------- Recharger le contenu du drawer via AJAX -------------------- */
async function reloadCartDrawer() {
  const res = await fetch(window.routes.panel);
  if (!res.ok) {
    console.error('Erreur lors du rechargement du panneau panier');
    return;
  }

  const data = await res.json();
  const drawerContainer = document.getElementById('cart-drawer');

  if (drawerContainer) {
    drawerContainer.innerHTML = data.html;
    attachCartDrawerEvents(); // Rebranche les événements sur le nouveau contenu
  }
}

/* -------------------- Ajouter un produit au panier -------------------- */
async function addToCart(variantId, qty = 1) {
  if (!userId) {
    alert('Veuillez vous connecter pour commander.');
    return;
  }

  const body = {
    variant: String(variantId),
    qty: +qty
  };

  const res = await fetch('/panier', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  });

  if (!res.ok) {
    console.error(await res.text());
    return;
  }

  await reloadCartDrawer(); // Recharge le panneau dynamique après ajout
}
window.addToCart = addToCart;

/* -------------------- Initialisation -------------------- */
document.addEventListener('DOMContentLoaded', () => {
  if (userId) {
    reloadCartDrawer();
  }

  attachCartDrawerEvents(); // Branche les événements initiaux

  // Boutons "Ajouter au panier"
  document.querySelectorAll('.js-addcart-detail').forEach(btn => {
    btn.addEventListener('click', e => {
      addToCart(
        e.currentTarget.dataset.variantId,
        e.currentTarget.dataset.qty ?? 1
      );
    });
  });
});

// ===========================================================
// ===========================================================
//  Template scripts (UI/UX)
// ===========================================================

(function ($) {
  "use strict";

  $(".animsition").animsition({
    inClass: 'fade-in',
    outClass: 'fade-out',
    inDuration: 1500,
    outDuration: 800,
    linkElement: '.animsition-link',
    loading: true,
    loadingParentElement: 'html',
    loadingClass: 'animsition-loading-1',
    loadingInner: '<div class="loader05"></div>',
    transition: function(url){ window.location.href = url; }
  });

  var windowH = $(window).height()/2;

  $(window).on('scroll',function(){
    if ($(this).scrollTop() > windowH) {
      $("#myBtn").css('display','flex');
    } else {
      $("#myBtn").css('display','none');
    }
  });

  $('#myBtn').on("click", function(){
    $('html, body').animate({scrollTop: 0}, 300);
  });

  var headerDesktop = $('.container-menu-desktop');
  var wrapMenu = $('.wrap-menu-desktop');

  var posWrapHeader = $('.top-bar').length > 0 ? $('.top-bar').height() : 0;

  if($(window).scrollTop() > posWrapHeader) {
    $(headerDesktop).addClass('fix-menu-desktop');
    $(wrapMenu).css('top',0); 
  } else {
    $(headerDesktop).removeClass('fix-menu-desktop');
    $(wrapMenu).css('top',posWrapHeader - $(this).scrollTop()); 
  }

  $(window).on('scroll',function(){
    if($(this).scrollTop() > posWrapHeader) {
      $(headerDesktop).addClass('fix-menu-desktop');
      $(wrapMenu).css('top',0); 
    } else {
      $(headerDesktop).removeClass('fix-menu-desktop');
      $(wrapMenu).css('top',posWrapHeader - $(this).scrollTop()); 
    } 
  });

  $('.btn-show-menu-mobile').on('click', function(){
    $(this).toggleClass('is-active');
    $('.menu-mobile').slideToggle();
  });

  var arrowMainMenu = $('.arrow-main-menu-m');
  arrowMainMenu.each(function () {
    $(this).on('click', function () {
      $(this).parent().find('.sub-menu-m').slideToggle();
      $(this).toggleClass('turn-arrow-main-menu-m');
    });
  });

  $(window).resize(function(){
    if($(window).width() >= 992){
      $('.menu-mobile').hide();
      $('.btn-show-menu-mobile').removeClass('is-active');
      $('.sub-menu-m').hide();
      arrowMainMenu.removeClass('turn-arrow-main-menu-m');
    }
  });

  $('.js-show-modal-search').on('click', function(){
    $('.modal-search-header').addClass('show-modal-search');
    $(this).css('opacity','0');
  });

  $('.js-hide-modal-search').on('click', function(){
    $('.modal-search-header').removeClass('show-modal-search');
    $('.js-show-modal-search').css('opacity','1');
  });

  $('.container-search-header').on('click', function(e){
    e.stopPropagation();
  });

  $(window).on('load', function () {
    var $grid = $('.isotope-grid').isotope({
      itemSelector: '.isotope-item',
      layoutMode: 'fitRows',
      percentPosition: true,
      animationEngine: 'best-available',
    });

    $('.filter-tope-group').on('click', 'button', function () {
      var filterValue = $(this).attr('data-filter');
      $grid.isotope({ filter: filterValue });

      $('.filter-tope-group button').removeClass('how-active1');
      $(this).addClass('how-active1');
    });
  });

  $('.js-show-filter').on('click',function(){
    $(this).toggleClass('show-filter');
    $('.panel-filter').slideToggle(400);
    if($('.js-show-search').hasClass('show-search')) {
      $('.js-show-search').removeClass('show-search');
      $('.panel-search').slideUp(400);
    }    
  });

  $('.js-show-search').on('click',function(){
    $(this).toggleClass('show-search');
    $('.panel-search').slideToggle(400);
    if($('.js-show-filter').hasClass('show-filter')) {
      $('.js-show-filter').removeClass('show-filter');
      $('.panel-filter').slideUp(400);
    }    
  });

  $('.btn-num-product-down').on('click', function(){
    var numProduct = Number($(this).next().val());
    if(numProduct > 0) $(this).next().val(numProduct - 1);
  });

  $('.btn-num-product-up').on('click', function(){
    var numProduct = Number($(this).prev().val());
    $(this).prev().val(numProduct + 1);
  });

  $('.wrap-rating').each(function(){
    var item = $(this).find('.item-rating');
    var rated = -1;
    var input = $(this).find('input');
    $(input).val(0);

    $(item).on('mouseenter', function(){
      var index = item.index(this);
      for(let i=0; i<=index; i++) {
        $(item[i]).removeClass('zmdi-star-outline').addClass('zmdi-star');
      }
      for(let j=index+1; j<item.length; j++) {
        $(item[j]).addClass('zmdi-star-outline').removeClass('zmdi-star');
      }
    });

    $(item).on('click', function(){
      rated = item.index(this);
      $(input).val(rated + 1);
    });

    $(this).on('mouseleave', function(){
      for(let i=0; i<=rated; i++) {
        $(item[i]).removeClass('zmdi-star-outline').addClass('zmdi-star');
      }
      for(let j=rated+1; j<item.length; j++) {
        $(item[j]).addClass('zmdi-star-outline').removeClass('zmdi-star');
      }
    });
  });

  $('.js-show-modal1').on('click',function(e){
    e.preventDefault();
    $('.js-modal1').addClass('show-modal1');
  });

  $('.js-hide-modal1').on('click',function(){
    $('.js-modal1').removeClass('show-modal1');
  });

})(jQuery);
