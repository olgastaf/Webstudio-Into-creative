<script>
BX.ready(function () {
  function initCarousel($scope) {
    $scope.find('.hit-carousel').each(function () {
      var $carousel = $(this);

      if ($carousel.data('dwCarouselReady')) return;

      $carousel.dwCarousel({
        slideItem: ".slideItem",
        slideBox: ".slideBox",
        countElement: 5,
        resizeElement: true,
        resizeAutoParams: {
          "1600": 5,
          "1400": 5,
          "1000": 4,
          "768": 3,
          "600": 2,
          "0": 1
        },
        severity: 8,
        speed: 400,
        leftButton: ".hit-carousel-prev",
        rightButton: ".hit-carousel-next"
      });

      $carousel.data('dwCarouselReady', true);
      $carousel.addClass('is-ready');
    });
  }

  initCarousel($(document));

  $(document).on('click', '.js-tab', function () {
    var $tab = $(this);
    var $section = $tab.closest('section.js-carousel-section');
    var tab = $tab.data('tab');
    var offers = $section.data('offers');
    var $wrap = $section.find('.js-carousel-wrap');

    $section.find('.js-tab').removeClass('is-active');
    $tab.addClass('is-active');

    $.ajax({
      url: '/local/ajax/hit-carousel.php',
      type: 'POST',
      data: {
        tab: tab,
        offers: offers
      },
      success: function (html) {
        $wrap.html(html);
        initCarousel($section);
      }
    });
  });
});
</script>