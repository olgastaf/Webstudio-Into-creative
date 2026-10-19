<?define("INDEX_PAGE", "Y");?>

<?define("MAIN_PAGE", true);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Интернет-магазин электротехнических изделий DDV");
$APPLICATION->SetPageProperty("keywords", "электротехника, электрооборудование");
$APPLICATION->SetPageProperty("description", "Интернет-магазин электрооборудования и электротехнических изделий DDV");
$APPLICATION->SetTitle("Интернет-магазин электротехнических изделий DDV");?>
<?
	//include module
	\Bitrix\Main\Loader::includeModule("dw.deluxe");

	//vars
	$catalogIblockId = null;
	$arPriceCodes = array();

	//get template settings
	$arTemplateSettings = DwSettings::getInstance()->getCurrentSettings();
	if(!empty($arTemplateSettings)){
		$catalogIblockId = $arTemplateSettings["TEMPLATE_PRODUCT_IBLOCK_ID"];
		$arPriceCodes = explode(", ", $arTemplateSettings["TEMPLATE_PRICE_CODES"]);
	}
?>
<?$APPLICATION->IncludeComponent(
	"dresscode:slider", 
	"promoSlider", 
	array(
		"IBLOCK_TYPE" => "slider",
		"IBLOCK_ID" => "11",
		"CACHE_TYPE" => "Y",
		"CACHE_TIME" => "3600000",
		"PICTURE_WIDTH" => "1920",
		"PICTURE_HEIGHT" => "1080",
		"COMPONENT_TEMPLATE" => ".default"
	),
	false
);?>
<section id="advantages" class="main-section">
<div class="container">
<?$APPLICATION->IncludeComponent(
	"bitrix:main.include", 
	".default", 
	[
		"AREA_FILE_SHOW" => "file",
		"EDIT_TEMPLATE" => "",
		"COMPONENT_TEMPLATE" => ".default",
		"PATH" => "/include/advantages.php"
	],
	false
);?>
	</div>
	</section>
<section id="pop_section" class="main-section">
<div class="container">
<?$APPLICATION->IncludeComponent(
	"dresscode:pop.section", 
	".default", 
	[
		"CACHE_TYPE" => "Y",
		"CACHE_TIME" => "3600000",
		"PROP_NAME" => "UF_POPULAR",
		"IBLOCK_TYPE" => "catalog",
		"IBLOCK_ID" => "19",
		"PICTURE_WIDTH" => "300",
		"PICTURE_HEIGHT" => "300",
		"PROP_VALUE" => "Y",
		"ELEMENTS_COUNT" => "10",
		"SORT_PROPERTY_NAME" => "sort",
		"SORT_VALUE" => "ASC",
		"SELECT_FIELDS" => [
			0 => "NAME",
			1 => "SECTION_PAGE_URL",
			2 => "DETAIL_PICTURE",
			3 => "UF_IMAGES",
			4 => "UF_MARKER",
			5 => "",
		],
		"POP_LAST_ELEMENT" => "Y",
		"COMPONENT_TEMPLATE" => ".default",
		"LAZY_LOAD_PICTURES" => "N"
	],
	false,
	[
		"ACTIVE_COMPONENT" => "Y"
	]
);?>
	</div>
	</section>

<?php
$activeTab = 'all';

$tabFilters = [
    'all'        => [],
    'automation' => ['=PROPERTY_USAGE' => 72],
    'shield'     => ['=PROPERTY_USAGE' => 73],
    'mount'      => ['=PROPERTY_USAGE' => 74],
    'home'       => ['=PROPERTY_USAGE' => 75],
    'lighting'   => ['=PROPERTY_USAGE' => 76],
];

global $arrFilter;
$arrFilter = array_merge(
    ['=PROPERTY_OFFERS' => 27],
    $tabFilters[$activeTab]
);
?>

<section id="hits" data-offers="27" class="main-section js-carousel-section">
<div class="container">
	<div class="section__title">Хиты продаж</div>


<div class="js-tabs">
    <button type="button" class="js-tab is-active" data-tab="all">Все товары</button>
    <button type="button" class="js-tab" data-tab="automation">Для автоматизации</button>
    <button type="button" class="js-tab" data-tab="shield">Сборка щита</button>
    <button type="button" class="js-tab" data-tab="mount">Для удобного монтажа</button>
    <button type="button" class="js-tab" data-tab="home">Для дома</button>
    <button type="button" class="js-tab" data-tab="lighting">Освещение</button>
</div>

<div class="js-carousel-wrap">
<? 
global $arrFilter;
$arrFilter = [
    '=PROPERTY_OFFERS' => 27,
];
$APPLICATION->IncludeComponent(
	"bitrix:catalog.section", 
	"slider", 
	[
		"ACTION_VARIABLE" => "action",
		"ADD_PICT_PROP" => "-",
		"ADD_PROPERTIES_TO_BASKET" => "Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"ADD_TO_BASKET_ACTION" => "ADD",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"BACKGROUND_IMAGE" => "-",
		"BASKET_URL" => "/personal/basket.php",
		"BROWSER_TITLE" => "-",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"COMPATIBLE_MODE" => "N",
		"CONVERT_CURRENCY" => "N",
		"CUSTOM_FILTER" => "{\"CLASS_ID\":\"CondGroup\",\"DATA\":{\"All\":\"AND\",\"True\":\"True\"},\"CHILDREN\":[]}",
		"DETAIL_URL" => "",
		"DISABLE_INIT_JS_IN_COMPONENT" => "N",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"DISPLAY_COMPARE" => "Y",
		"DISPLAY_TOP_PAGER" => "N",
		"ELEMENT_SORT_FIELD" => "sort",
		"ELEMENT_SORT_FIELD2" => "id",
		"ELEMENT_SORT_ORDER" => "asc",
		"ELEMENT_SORT_ORDER2" => "desc",
		"ENLARGE_PRODUCT" => "STRICT",
		"FILTER_NAME" => "arrFilter",
		"HIDE_NOT_AVAILABLE" => "N",
		"HIDE_NOT_AVAILABLE_OFFERS" => "N",
		"IBLOCK_ID" => "19",
		"IBLOCK_TYPE" => "catalog",
		"INCLUDE_SUBSECTIONS" => "Y",
		"LABEL_PROP" => [
			0 => "OFFERS",
		],
		"LAZY_LOAD" => "N",
		"LINE_ELEMENT_COUNT" => "3",
		"LOAD_ON_SCROLL" => "N",
		"MESSAGE_404" => "",
		"MESS_BTN_ADD_TO_BASKET" => "В корзину",
		"MESS_BTN_BUY" => "Купить",
		"MESS_BTN_DETAIL" => "Подробнее",
		"MESS_BTN_LAZY_LOAD" => "Показать ещё",
		"MESS_BTN_SUBSCRIBE" => "Подписаться",
		"MESS_NOT_AVAILABLE" => "Нет в наличии",
		"MESS_NOT_AVAILABLE_SERVICE" => "Недоступно",
		"META_DESCRIPTION" => "-",
		"META_KEYWORDS" => "-",
		"OFFERS_FIELD_CODE" => [
			0 => "",
			1 => "",
		],
		"OFFERS_LIMIT" => "5",
		"OFFERS_SORT_FIELD" => "sort",
		"OFFERS_SORT_FIELD2" => "id",
		"OFFERS_SORT_ORDER" => "asc",
		"OFFERS_SORT_ORDER2" => "desc",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "Товары",
		"PAGE_ELEMENT_COUNT" => "200",
		"PARTIAL_PRODUCT_PROPERTIES" => "N",
		"PRICE_CODE" => [
			0 => "BASE",
		],
		"PRICE_VAT_INCLUDE" => "Y",
		"PRODUCT_BLOCKS_ORDER" => "quantityLimit,price,props,sku,quantity,buttons",
		"PRODUCT_DISPLAY_MODE" => "Y",
		"PRODUCT_ID_VARIABLE" => "id",
		"PRODUCT_PROPS_VARIABLE" => "prop",
		"PRODUCT_QUANTITY_VARIABLE" => "quantity",
		"PRODUCT_SUBSCRIPTION" => "Y",
		"SECTION_CODE" => "",
		"SECTION_ID" =>  "",
		"SECTION_ID_VARIABLE" => "SECTION_ID",
		"SECTION_URL" => "",
		"SECTION_USER_FIELDS" => [
			0 => "",
			1 => "",
		],
		"SEF_MODE" => "N",
		"SET_BROWSER_TITLE" => "Y",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "Y",
		"SET_META_KEYWORDS" => "Y",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "Y",
		"SHOW_404" => "N",
		"SHOW_ALL_WO_SECTION" => "Y",
		"SHOW_CLOSE_POPUP" => "Y",
		"SHOW_DISCOUNT_PERCENT" => "Y",
		"SHOW_MAX_QUANTITY" => "M",
		"SHOW_OLD_PRICE" => "Y",
		"SHOW_PRICE_COUNT" => "1",
		"SHOW_SLIDER" => "N",
		"SLIDER_INTERVAL" => "3000",
		"SLIDER_PROGRESS" => "N",
		"TEMPLATE_THEME" => "red",
		"USE_ENHANCED_ECOMMERCE" => "N",
		"USE_MAIN_ELEMENT_SECTION" => "N",
		"USE_PRICE_COUNT" => "N",
		"USE_PRODUCT_QUANTITY" => "Y",
		"COMPONENT_TEMPLATE" => "slider",
		"PROPERTY_CODE_MOBILE" => [
		],
		"COMPARE_PATH" => "",
		"USE_COMPARE_LIST" => "N",
		"MESS_BTN_COMPARE" => "Сравнить",
		"COMPARE_NAME" => "CATALOG_COMPARE_LIST",
		"LABEL_PROP_MOBILE" => [
			0 => "OFFERS",
		],
		"LABEL_PROP_POSITION" => "top-left",
		"OFFER_ADD_PICT_PROP" => "-",
		"DISCOUNT_PERCENT_POSITION" => "top-left",
		"MESS_SHOW_MAX_QUANTITY" => "Наличие",
		"RELATIVE_QUANTITY_FACTOR" => "5",
		"MESS_RELATIVE_QUANTITY_MANY" => "много",
		"MESS_RELATIVE_QUANTITY_FEW" => "мало"
	],
	false
);?>
	</div>
	</div>
	</section>

<section id="aktsii" class="main-section">
<div class="container">
<?$APPLICATION->IncludeComponent(
	"bitrix:news.list", 
	"aktsii", 
	[
		"IBLOCK_TYPE" => "info",
		"IBLOCK_ID" => "10",
		"NEWS_COUNT" => "20",
		"SORT_BY1" => "SORT",
		"SORT_ORDER1" => "ASC",
		"SORT_BY2" => "NAME",
		"SORT_ORDER2" => "ASC",
		"FIELD_CODE" => [
			0 => "",
			1 => "",
			2 => "",
		],
		"PROPERTY_CODE" => [
			0 => "",
			1 => "",
			2 => "",
		],
		"DETAIL_URL" => "",
		"SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
		"IBLOCK_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["news"],
		"DISPLAY_PANEL" => $arParams["DISPLAY_PANEL"],
		"SET_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"MESSAGE_404" => "",
		"SET_STATUS_404" => "N",
		"SHOW_404" => "N",
		"FILE_404" => $arParams["FILE_404"],
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "N",
		"DISPLAY_TOP_PAGER" => "N",
		"DISPLAY_BOTTOM_PAGER" => "N",
		"PAGER_TITLE" => "",
		"PAGER_TEMPLATE" => "round",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_BASE_LINK" => $arParams["PAGER_BASE_LINK"],
		"PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
		"DISPLAY_DATE" => $arParams["DISPLAY_DATE"],
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => $arParams["DISPLAY_PICTURE"],
		"DISPLAY_PREVIEW_TEXT" => $arParams["DISPLAY_PREVIEW_TEXT"],
		"PREVIEW_TRUNCATE_LEN" => "",
		"ACTIVE_DATE_FORMAT" => $arParams["LIST_ACTIVE_DATE_FORMAT"],
		"USE_PERMISSIONS" => $arParams["USE_PERMISSIONS"],
		"GROUP_PERMISSIONS" => $arParams["GROUP_PERMISSIONS"],
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"CHECK_DATES" => "N",
		"COMPONENT_TEMPLATE" => "aktsii",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"SET_BROWSER_TITLE" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_META_DESCRIPTION" => "N",
		"ADD_SECTIONS_CHAIN" => "N",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"INCLUDE_SUBSECTIONS" => "N",
		"STRICT_SECTION_CHECK" => "N"
	],
	$component
);?>
	</div>
	</section>

<section id="news" data-offers="26" class="main-section js-carousel-section">
<div class="container">
	<div class="section__title">Новинки</div>


<div class="js-tabs">
    <button type="button" class="js-tab is-active" data-tab="all">Все товары</button>
    <button type="button" class="js-tab" data-tab="automation">Для автоматизации</button>
    <button type="button" class="js-tab" data-tab="shield">Сборка щита</button>
    <button type="button" class="js-tab" data-tab="mount">Для удобного монтажа</button>
    <button type="button" class="js-tab" data-tab="home">Для дома</button>
    <button type="button" class="js-tab" data-tab="lighting">Освещение</button>
</div>

<div class="js-carousel-wrap">
<? global $arrFilter;
$arrFilter = [
    '=PROPERTY_OFFERS' => 26,
]; 
$APPLICATION->IncludeComponent(
	"bitrix:catalog.section", 
	"slider", 
	[
		"ACTION_VARIABLE" => "action",
		"ADD_PICT_PROP" => "-",
		"ADD_PROPERTIES_TO_BASKET" => "Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"ADD_TO_BASKET_ACTION" => "ADD",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"BACKGROUND_IMAGE" => "-",
		"BASKET_URL" => "/personal/basket.php",
		"BROWSER_TITLE" => "-",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"COMPATIBLE_MODE" => "N",
		"CONVERT_CURRENCY" => "N",
		"CUSTOM_FILTER" => "{\"CLASS_ID\":\"CondGroup\",\"DATA\":{\"All\":\"AND\",\"True\":\"True\"},\"CHILDREN\":[]}",
		"DETAIL_URL" => "",
		"DISABLE_INIT_JS_IN_COMPONENT" => "N",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"DISPLAY_COMPARE" => "Y",
		"DISPLAY_TOP_PAGER" => "N",
		"ELEMENT_SORT_FIELD" => "sort",
		"ELEMENT_SORT_FIELD2" => "id",
		"ELEMENT_SORT_ORDER" => "asc",
		"ELEMENT_SORT_ORDER2" => "desc",
		"ENLARGE_PRODUCT" => "STRICT",
		"FILTER_NAME" => "arrFilter",
		"HIDE_NOT_AVAILABLE" => "N",
		"HIDE_NOT_AVAILABLE_OFFERS" => "N",
		"IBLOCK_ID" => "19",
		"IBLOCK_TYPE" => "catalog",
		"INCLUDE_SUBSECTIONS" => "Y",
		"LABEL_PROP" => [
			0 => "OFFERS",
		],
		"LAZY_LOAD" => "N",
		"LINE_ELEMENT_COUNT" => "3",
		"LOAD_ON_SCROLL" => "N",
		"MESSAGE_404" => "",
		"MESS_BTN_ADD_TO_BASKET" => "В корзину",
		"MESS_BTN_BUY" => "Купить",
		"MESS_BTN_DETAIL" => "Подробнее",
		"MESS_BTN_LAZY_LOAD" => "Показать ещё",
		"MESS_BTN_SUBSCRIBE" => "Подписаться",
		"MESS_NOT_AVAILABLE" => "Нет в наличии",
		"MESS_NOT_AVAILABLE_SERVICE" => "Недоступно",
		"META_DESCRIPTION" => "-",
		"META_KEYWORDS" => "-",
		"OFFERS_FIELD_CODE" => [
			0 => "",
			1 => "",
		],
		"OFFERS_LIMIT" => "5",
		"OFFERS_SORT_FIELD" => "sort",
		"OFFERS_SORT_FIELD2" => "id",
		"OFFERS_SORT_ORDER" => "asc",
		"OFFERS_SORT_ORDER2" => "desc",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "Товары",
		"PAGE_ELEMENT_COUNT" => "200",
		"PARTIAL_PRODUCT_PROPERTIES" => "N",
		"PRICE_CODE" => [
			0 => "BASE",
		],
		"PRICE_VAT_INCLUDE" => "Y",
		"PRODUCT_BLOCKS_ORDER" => "quantityLimit,price,props,sku,quantity,buttons",
		"PRODUCT_DISPLAY_MODE" => "Y",
		"PRODUCT_ID_VARIABLE" => "id",
		"PRODUCT_PROPS_VARIABLE" => "prop",
		"PRODUCT_QUANTITY_VARIABLE" => "quantity",
		"PRODUCT_SUBSCRIPTION" => "Y",
		"SECTION_CODE" => "",
		"SECTION_ID" =>  "",
		"SECTION_ID_VARIABLE" => "SECTION_ID",
		"SECTION_URL" => "",
		"SECTION_USER_FIELDS" => [
			0 => "",
			1 => "",
		],
		"SEF_MODE" => "N",
		"SET_BROWSER_TITLE" => "Y",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "Y",
		"SET_META_KEYWORDS" => "Y",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "Y",
		"SHOW_404" => "N",
		"SHOW_ALL_WO_SECTION" => "Y",
		"SHOW_CLOSE_POPUP" => "Y",
		"SHOW_DISCOUNT_PERCENT" => "Y",
		"SHOW_MAX_QUANTITY" => "M",
		"SHOW_OLD_PRICE" => "Y",
		"SHOW_PRICE_COUNT" => "1",
		"SHOW_SLIDER" => "N",
		"SLIDER_INTERVAL" => "3000",
		"SLIDER_PROGRESS" => "N",
		"TEMPLATE_THEME" => "red",
		"USE_ENHANCED_ECOMMERCE" => "N",
		"USE_MAIN_ELEMENT_SECTION" => "N",
		"USE_PRICE_COUNT" => "N",
		"USE_PRODUCT_QUANTITY" => "Y",
		"COMPONENT_TEMPLATE" => "slider",
		"PROPERTY_CODE_MOBILE" => [
		],
		"COMPARE_PATH" => "",
		"USE_COMPARE_LIST" => "N",
		"MESS_BTN_COMPARE" => "Сравнить",
		"COMPARE_NAME" => "CATALOG_COMPARE_LIST",
		"LABEL_PROP_MOBILE" => [
			0 => "OFFERS",
		],
		"LABEL_PROP_POSITION" => "top-left",
		"OFFER_ADD_PICT_PROP" => "-",
		"DISCOUNT_PERCENT_POSITION" => "top-left",
		"MESS_SHOW_MAX_QUANTITY" => "Наличие",
		"RELATIVE_QUANTITY_FACTOR" => "5",
		"MESS_RELATIVE_QUANTITY_MANY" => "много",
		"MESS_RELATIVE_QUANTITY_FEW" => "мало"
	],
	false
);?>
	</div>
	</div>
	</section>

<section id="best" data-offers="29" class="main-section js-carousel-section">
<div class="container">
	<div class="section__title">Лучшие предложения</div>


<div class="js-tabs">
    <button type="button" class="js-tab is-active" data-tab="all">Все товары</button>
    <button type="button" class="js-tab" data-tab="automation">Для автоматизации</button>
    <button type="button" class="js-tab" data-tab="shield">Сборка щита</button>
    <button type="button" class="js-tab" data-tab="mount">Для удобного монтажа</button>
    <button type="button" class="js-tab" data-tab="home">Для дома</button>
    <button type="button" class="js-tab" data-tab="lighting">Освещение</button>
</div>

<div class="js-carousel-wrap">
<? 
global $arrFilter;
$arrFilter = [
    '=PROPERTY_OFFERS' => 29,
];
$APPLICATION->IncludeComponent(
	"bitrix:catalog.section", 
	"slider", 
	[
		"ACTION_VARIABLE" => "action",
		"ADD_PICT_PROP" => "-",
		"ADD_PROPERTIES_TO_BASKET" => "Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"ADD_TO_BASKET_ACTION" => "ADD",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"BACKGROUND_IMAGE" => "-",
		"BASKET_URL" => "/personal/basket.php",
		"BROWSER_TITLE" => "-",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"COMPATIBLE_MODE" => "N",
		"CONVERT_CURRENCY" => "N",
		"CUSTOM_FILTER" => "{\"CLASS_ID\":\"CondGroup\",\"DATA\":{\"All\":\"AND\",\"True\":\"True\"},\"CHILDREN\":[]}",
		"DETAIL_URL" => "",
		"DISABLE_INIT_JS_IN_COMPONENT" => "N",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"DISPLAY_COMPARE" => "Y",
		"DISPLAY_TOP_PAGER" => "N",
		"ELEMENT_SORT_FIELD" => "sort",
		"ELEMENT_SORT_FIELD2" => "id",
		"ELEMENT_SORT_ORDER" => "asc",
		"ELEMENT_SORT_ORDER2" => "desc",
		"ENLARGE_PRODUCT" => "STRICT",
		"FILTER_NAME" => "arrFilter",
		"HIDE_NOT_AVAILABLE" => "N",
		"HIDE_NOT_AVAILABLE_OFFERS" => "N",
		"IBLOCK_ID" => "19",
		"IBLOCK_TYPE" => "catalog",
		"INCLUDE_SUBSECTIONS" => "Y",
		"LABEL_PROP" => [
			0 => "OFFERS",
		],
		"LAZY_LOAD" => "N",
		"LINE_ELEMENT_COUNT" => "3",
		"LOAD_ON_SCROLL" => "N",
		"MESSAGE_404" => "",
		"MESS_BTN_ADD_TO_BASKET" => "В корзину",
		"MESS_BTN_BUY" => "Купить",
		"MESS_BTN_DETAIL" => "Подробнее",
		"MESS_BTN_LAZY_LOAD" => "Показать ещё",
		"MESS_BTN_SUBSCRIBE" => "Подписаться",
		"MESS_NOT_AVAILABLE" => "Нет в наличии",
		"MESS_NOT_AVAILABLE_SERVICE" => "Недоступно",
		"META_DESCRIPTION" => "-",
		"META_KEYWORDS" => "-",
		"OFFERS_FIELD_CODE" => [
			0 => "",
			1 => "",
		],
		"OFFERS_LIMIT" => "5",
		"OFFERS_SORT_FIELD" => "sort",
		"OFFERS_SORT_FIELD2" => "id",
		"OFFERS_SORT_ORDER" => "asc",
		"OFFERS_SORT_ORDER2" => "desc",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "Товары",
		"PAGE_ELEMENT_COUNT" => "200",
		"PARTIAL_PRODUCT_PROPERTIES" => "N",
		"PRICE_CODE" => [
			0 => "BASE",
		],
		"PRICE_VAT_INCLUDE" => "Y",
		"PRODUCT_BLOCKS_ORDER" => "quantityLimit,price,props,sku,quantity,buttons",
		"PRODUCT_DISPLAY_MODE" => "Y",
		"PRODUCT_ID_VARIABLE" => "id",
		"PRODUCT_PROPS_VARIABLE" => "prop",
		"PRODUCT_QUANTITY_VARIABLE" => "quantity",
		"PRODUCT_SUBSCRIPTION" => "Y",
		"SECTION_CODE" => "",
		"SECTION_ID" =>  "",
		"SECTION_ID_VARIABLE" => "SECTION_ID",
		"SECTION_URL" => "",
		"SECTION_USER_FIELDS" => [
			0 => "",
			1 => "",
		],
		"SEF_MODE" => "N",
		"SET_BROWSER_TITLE" => "Y",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "Y",
		"SET_META_KEYWORDS" => "Y",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "Y",
		"SHOW_404" => "N",
		"SHOW_ALL_WO_SECTION" => "Y",
		"SHOW_CLOSE_POPUP" => "Y",
		"SHOW_DISCOUNT_PERCENT" => "Y",
		"SHOW_MAX_QUANTITY" => "M",
		"SHOW_OLD_PRICE" => "Y",
		"SHOW_PRICE_COUNT" => "1",
		"SHOW_SLIDER" => "N",
		"SLIDER_INTERVAL" => "3000",
		"SLIDER_PROGRESS" => "N",
		"TEMPLATE_THEME" => "red",
		"USE_ENHANCED_ECOMMERCE" => "N",
		"USE_MAIN_ELEMENT_SECTION" => "N",
		"USE_PRICE_COUNT" => "N",
		"USE_PRODUCT_QUANTITY" => "Y",
		"COMPONENT_TEMPLATE" => "slider",
		"PROPERTY_CODE_MOBILE" => [
		],
		"COMPARE_PATH" => "",
		"USE_COMPARE_LIST" => "N",
		"MESS_BTN_COMPARE" => "Сравнить",
		"COMPARE_NAME" => "CATALOG_COMPARE_LIST",
		"LABEL_PROP_MOBILE" => [
			0 => "OFFERS",
		],
		"LABEL_PROP_POSITION" => "top-left",
		"OFFER_ADD_PICT_PROP" => "-",
		"DISCOUNT_PERCENT_POSITION" => "top-left",
		"MESS_SHOW_MAX_QUANTITY" => "Наличие",
		"RELATIVE_QUANTITY_FACTOR" => "5",
		"MESS_RELATIVE_QUANTITY_MANY" => "много",
		"MESS_RELATIVE_QUANTITY_FEW" => "мало"
	],
	false
);?>
	</div>
	</div>
	</section>
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

<section class="main-section">
<div class="container">
<div id="infoTabs">
	<div class="items">


<?$APPLICATION->IncludeComponent(
	"bitrix:main.include", 
	".default", 
	array(
		"COMPONENT_TEMPLATE" => ".default",
		"AREA_FILE_SHOW" => "sect",
		"AREA_FILE_SUFFIX" => "simplyText",
		"AREA_FILE_RECURSIVE" => "Y",
		"EDIT_TEMPLATE" => ""
	),
	false
);?>
	</div>
	</div>
</div>
</section>

