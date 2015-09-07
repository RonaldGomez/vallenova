
var appMaster = {
  preLoader: function(){
    imageSources = []
    $('img').each(function() {
      var sources = $(this).attr('src');
      imageSources.push(sources);
    });
    if($(imageSources).load()){
      $('.pre-loader').fadeOut('slow');
    }
  },
  animateScript: function() {
    $('.scrollpoint.sp-effect1').waypoint(function(){$(this).toggleClass('active');$(this).toggleClass('animated fadeInLeft');},{offset:'100%'});
    $('.scrollpoint.sp-effect2').waypoint(function(){$(this).toggleClass('active');$(this).toggleClass('animated fadeInRight');},{offset:'100%'});
    $('.scrollpoint.sp-effect3').waypoint(function(){$(this).toggleClass('active');$(this).toggleClass('animated fadeInDown');},{offset:'100%'});
    $('.scrollpoint.sp-effect4').waypoint(function(){$(this).toggleClass('active');$(this).toggleClass('animated fadeIn');},{offset:'100%'});
    $('.scrollpoint.sp-effect5').waypoint(function(){$(this).toggleClass('active');$(this).toggleClass('animated fadeInUp');},{offset:'100%'});
    $('.scrollpoint.sp-effect6').waypoint(function(){$(this).toggleClass('active');$(this).toggleClass('animated rotateIn');},{offset:'100%'});
    $('.scrollpoint.sp-effect7').waypoint(function(){$(this).toggleClass('active');$(this).toggleClass('animated pulse');},{offset:'100%'});
  }
    
}; // AppMaster

$(document).ready(function() {
  //d = new Date(); h = d.getHours(); if (h > 21) { $(".header").toggleClass('header4');}else{if(h > 16) {$(".header").toggleClass('header3');}else{if(h > 10){$(".header").toggleClass('header2');}else{if (h > 5) {$(".header").toggleClass('header1');}else{$(".header").toggleClass('header4');}}}}
  $("#bottom-home").hide();

  $(window).scroll(function() {
    if ($(this).scrollTop() > 400) {
      $('#bottom-home').fadeIn();
    } 
    else {
      $('#bottom-home').fadeOut();
    }
  });

  // Closes the sidebar menu
  $("#menu-close").click(function(e) {
      e.preventDefault();
      $("#sidebar-wrapper").toggleClass("active");
  });

  // Opens the sidebar menu
  $("#menu-toggle").click(function(e) {
      e.preventDefault();
      $("#sidebar-wrapper").toggleClass("active");
  });

  // --------------------------------------------------------
  //  Smooth Scrolling
  // --------------------------------------------------------   
  $(".sidebar-nav li a[href^='#']").on('click', function(e) {
    e.preventDefault();
    $('html, body').animate({
        scrollTop: $(this.hash).offset().top
    }, 1000);
  });

  $(".tp-banner li a[href^='#']").on('click', function(e) {
    e.preventDefault();
    $('html, body').animate({
        scrollTop: $(this.hash).offset().top
    }, 1000);
  });

  $(".footer-content li a[href^='#']").on('click', function(e) {
    e.preventDefault();
    $('html, body').animate({
        scrollTop: $(this.hash).offset().top
    }, 1000);
  });

  $(".subfooter nav li a[href^='#']").on('click', function(e) {
    e.preventDefault();
    $('html, body').animate({
        scrollTop: $(this.hash).offset().top
    }, 1000);
  });

  $("#nosotros a[href^='#']").on('click', function(e) {
    e.preventDefault();
    $('html, body').animate({
        scrollTop: $(this.hash).offset().top
    }, 1000);
  });

  $("#bottom-home a[href^='#']").on('click', function(e) {
    e.preventDefault();
    $('html, body').animate({
        scrollTop: $(this.hash).offset().top
    }, 1000);
  });

  appMaster.animateScript();
  appMaster.preLoader();

  var revapi;
  revapi = $('.tp-banner').revolution(
  {
    delay:9000,
    startwidth:1170,
    startheight:500,
    hideThumbs:10,
    onHoverStop: "off",
    fullWidth:"off",
    fullScreen:"on",
    fullScreenOffsetContainer: ""
  });

});
