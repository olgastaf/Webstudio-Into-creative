<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Title");
?>
<?
\Bitrix\Main\Page\Asset::getInstance()->addJs(
    '/local/include/offers-carousel.js'
);
?>

<?php
// Хиты продаж
$sectionCode = 'hits';
include $_SERVER['DOCUMENT_ROOT'] . '/local/include/section.php';
?>

<?php
// Здесь может быть другой контент главной страницы
?>

<?php
// Новинки
$sectionCode = 'news';
include $_SERVER['DOCUMENT_ROOT'] . '/local/include/section.php';
?>

<?php
// Здесь может быть другой контент главной страницы
?>

<?php
// Лучшие предложения
$sectionCode = 'best';
include $_SERVER['DOCUMENT_ROOT'] . '/local/include/section.php';
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>