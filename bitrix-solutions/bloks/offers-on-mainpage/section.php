<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$config = require __DIR__ . '/config.php';

if (empty($sectionCode) || !isset($config['sections'][$sectionCode])) {
    return;
}

$sectionConfig = $config['sections'][$sectionCode];

$iblockId = (int)$config['iblock_id'];
$offersId = (int)$sectionConfig['offers_id'];

$offersProperty = $config['properties']['offers'];
$usageProperty = $config['properties']['usage'];

$tabs = $config['tabs'];

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    return;
}

// Проверка наличия товаров во вкладке
$hasTabProducts = static function ($usageId = null) use (
    $iblockId,
    $offersId,
    $offersProperty,
    $usageProperty
) {
    $filter = [
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y',
        '=PROPERTY_' . $offersProperty => $offersId,
    ];

    if ($usageId !== null) {
        $filter['=PROPERTY_' . $usageProperty] = $usageId;
    }

    $result = CIBlockElement::GetList(
        [],
        $filter,
        false,
        ['nTopCount' => 1],
        ['ID']
    );

    return (bool)$result->Fetch();
};

// Не выводим блок, если в нём вообще нет товаров
if (!$hasTabProducts()) {
    return;
}

global $arrFilter;

$arrFilter = [
    '=PROPERTY_' . $offersProperty => $offersId,
];

?>

<section
    id="<?= htmlspecialcharsbx($sectionCode) ?>"
    data-offers="<?= $offersId ?>"
    class="main-section js-carousel-section"
>
    <div class="section__title">
        <?= htmlspecialcharsbx($sectionConfig['title']) ?>
    </div>

    <div class="js-tabs">
        <?php foreach ($tabs as $code => $tab): ?>
            <?php if ($hasTabProducts($tab['usage_id'])): ?>
                <button
                    type="button"
                    class="js-tab <?= $code === 'all' ? 'is-active' : '' ?>"
                    data-tab="<?= htmlspecialcharsbx($code) ?>"
                >
                    <?= htmlspecialcharsbx($tab['title']) ?>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="js-carousel-wrap">
        <?php
   <? $APPLICATION->IncludeComponent(
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
            "IBLOCK_ID" => $iblockId,
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
        ?>
    </div>
</section>