// MAIN JS

$(document).ready(function() {

  /* Mobile navigation*/

  $('header .menu-btn').on('click', function() {
    if (!$('html').hasClass('nav-animating')) {
      if ($('html').hasClass('nav-open')) {
        setTimeout(function() {
          $('.art-nav').removeClass('wide');
        }, 500);
      } else {
        $('.art-nav').addClass('wide');
      }
      $('.menu-btn button').toggleClass('close');
      $('html').toggleClass('nav-open').addClass('nav-animating');
      setTimeout(function() {
        $('html').removeClass('nav-animating');
      }, 400);
    }
  });
  $('html').on('click', function() {
    if (!$('html').hasClass('nav-animating')) {
      $('.menu-btn button').removeClass('close');
      $('html').removeClass('nav-open');
      setTimeout(function() {
        $('.art-nav').removeClass('wide');
      }, 500);
    }
  });
  $('.art-nav-center').on('click', function(e) {
    e.stopPropagation();
  });

});