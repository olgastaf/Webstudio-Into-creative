<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;

$aMenuLinksExt = $APPLICATION->IncludeComponent(
    "bitrix:menu.sections",
    "",
    array(
        "IS_SEF" => "Y",
        "SEF_BASE_URL" => "/catalog/",
        "SECTION_PAGE_URL" => "/catalog/#SECTION_CODE_PATH#/",
        "DETAIL_PAGE_URL" => "/catalog/#SECTION_CODE_PATH#/#ELEMENT_ID#/",
        "IBLOCK_TYPE" => "catalog",
        "IBLOCK_ID" => 19,
        "DEPTH_LEVEL" => "1",
        "CACHE_TYPE" => "A",
        "CACHE_TIME" => "3600"
    ),
    false,
    array("HIDE_ICONS" => "Y")
);

$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
?>