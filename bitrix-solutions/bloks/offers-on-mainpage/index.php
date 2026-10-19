<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Title");
?>

<?php
// Хиты продаж
$sectionCode = 'hits';
include __DIR__ . '/section.php';
?>

<?php
// Здесь может быть другой контент главной страницы
?>

<?php
// Новинки
$sectionCode = 'news';
include __DIR__ . '/section.php';
?>

<?php
// Здесь может быть другой контент главной страницы
?>

<?php
// Лучшие предложения
$sectionCode = 'best';
include __DIR__ . '/section.php';
?>

<script>
BX.ready(function () {
function initCarousel($scope) {
  $scope.find('.hit-carousel').each(function () {
    var carousel = this;
    var swiperElement = carousel.querySelector('.swiper');

    if (!swiperElement || swiperElement.swiper) {
      return;
    }

    var swiper = new Swiper(swiperElement, {
      slidesPerView: 5,
      spaceBetween: 8,
      speed: 400,
      watchOverflow: true,

      navigation: {
        prevEl: carousel.querySelector('.hit-carousel-prev'),
        nextEl: carousel.querySelector('.hit-carousel-next')
      },

      breakpoints: {
        0: {
          slidesPerView: 1
        },
        600: {
          slidesPerView: 2
        },
        768: {
          slidesPerView: 3
        },
        1000: {
          slidesPerView: 3
        },
        1400: {
          slidesPerView: 5
        }
      }
    });

    carousel.classList.add('is-ready');
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
      url: '/ajax/hit-carousel.php',
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

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>