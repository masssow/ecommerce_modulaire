// assets/js/main.js
import $ from 'jquery';
import 'animsition';

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

// ==========================================================
// Favorite button

(function(){
  function updateHeaderFavCount(count){
    // si tu as une icône en header avec badge (ex: data-notify)
    var b = document.querySelector('.icon-header-noti[data-fav-badge]');
    if(b){ b.setAttribute('data-notify', count); }
  }

  document.addEventListener('click', function(e){
    var btn = e.target.closest('.js-fav-toggle');
    if(!btn) return;
    e.preventDefault();

    var id = btn.getAttribute('data-variant-id');
    var token = btn.getAttribute('data-token');

    var form = new FormData();
    form.append('_token', token);

    fetch('/favorite/toggle/'+id, {
      method: 'POST',
      headers: {'X-Requested-With':'XMLHttpRequest'},
      body: form
    })
    .then(r => r.json())
    .then(json => {
      if(!json.ok) return;

      btn.classList.toggle('is-fav', json.active);
      updateHeaderFavCount(json.count);
    })
    .catch(()=>{ /* silence MVP */ });
  });
})();
// ============================================================
