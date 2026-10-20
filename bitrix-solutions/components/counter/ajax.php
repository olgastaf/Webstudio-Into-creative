<?php

use Bitrix\Catalog\Product\CatalogProvider;
use Bitrix\Catalog\Product\SubscribeManager;
use Bitrix\Catalog\SubscribeTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Engine\Response\Json as JsonResponse;
use Bitrix\Main\HttpContext;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\UserConsent\Consent;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Order;
use Bitrix\Sale\PersonType;

const STOP_STATISTICS = true;
const NO_AGENT_CHECK = true;

$siteId = $_GET['SITE_ID'] ?? $_GET['siteId'] ?? $_POST['SITE_ID'] ?? $_POST['siteId'] ?? '';

if (is_string($siteId) && preg_match('/^[a-zA-Z0-9]{2}$/', $siteId) === 1) {
	define('SITE_ID', $siteId);
}

$languageId = $_GET['LANGUAGE_ID'] ?? $_GET['languageId'] ?? $_POST['LANGUAGE_ID'] ?? $_POST['languageId'] ?? '';

if (is_string($languageId) && preg_match('/^[a-zA-Z0-9]{2}$/', $languageId) === 1) {
	define('LANGUAGE_ID', $languageId);
}

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

error_reporting(0);

Loc::loadMessages(__FILE__);

Loader::requireModule("dw.deluxe");

$application = Application::getInstance();

/**
 * @var HttpContext $context
 */
$context = $application->getContext();

/**
 * @var HttpRequest $request
 */
$request = $context->getRequest();
$session = $application->getSession();

$actionName = $_GET["action"] ?? $_GET["act"] ?? null;
$serverName = \COption::GetOptionString('main', 'server_name', $_SERVER['SERVER_NAME']);

global $USER;

if(!is_string($actionName) || $actionName === '') {
	$response = new JsonResponse(["errors" => "`Act` parameter is required"]);
	$response
		->setStatus(400)
		->send();

	$application->terminate();
}

if($actionName == "getUserAgreement"){
	$settings = \DwSettings::getInstance()->getCurrentSettings();
	$userAgreementId = $settings['TEMPLATE_AGREEMENT_ID'] ?? null;

	if($userAgreementId === null) {
		$response = new JsonResponse(["errors" => ["Cannot find agreement"]]);
		$response
			->setStatus(500)
			->send();

		$application->terminate();
	}

	$categories = $settings['TEMPLATE_AGREEMENT_PERSONAL_DATA_CATEGORIES'] ?? null;
	$categories = is_array($categories) ? $categories : [];

	$replacements = ['fields' => $categories];

	$response = new Component(
		'dresscode:user.agreement',
		'modal',
		[
			"AGREEMENT_ID" => $userAgreementId,
			"REPLACEMENTS" => $replacements,
			"CACHE_TYPE" => "A",
		]
	);
	$response->send();

	$application->terminate();
}
elseif($actionName == "getRequestPriceForm"){
	$settings = \DwSettings::getInstance()->getCurrentSettings();
	$usePhoneMask = $settings['TEMPLATE_USE_MASKED_INPUT'] ?? null;
	$phoneMaskFormat = $settings['MASKED_INPUT_CUSTOM_FORMAT'] ?? null;

	$productId = $_GET['productId'] ?? null;

	if($productId === null) {
		$response = new JsonResponse(["errors" => ["Product id parameter is required"]]);
		$response
			->setStatus(400)
			->send();

		$application->terminate();
	}

	$response = new Component(
		'dresscode:request.price.form',
		'modal',
		[
			"PRODUCT_ID" => $productId,
			"USE_PHONE_MASK" => $usePhoneMask,
			"PHONE_MASK_FORMAT" => $phoneMaskFormat,
			"CACHE_TYPE" => "A",
		]
	);
	$response->send();

	$application->terminate();
}
elseif($actionName === "requestPrice"){
    if (!check_bitrix_sessid()) {
		$response = new JsonResponse([
			"heading" => Loc::getMessage("AI0_DW_DELUXE_API_ERROR_TITLE"),
			"errors" => [
				Loc::getMessage("AI0_DW_DELUXE_API_CSRF_ERROR")
			],
			"success" => false
		]);

		$response
			->setStatus(403)
			->send();
		$application->terminate();
    }

	$settings = \DwSettings::getInstance()->getCurrentSettings();
	$userAgreementId = $settings['TEMPLATE_AGREEMENT_ID'] ?? null;

	$telephone = $_POST["telephone"] ?? null;
	$productId = $_POST["productId"] ?? null;
	$name = $_POST["name"] ?? '';
	$comment = $_POST["comment"] ?? '';

	if($telephone === null) {
		$response = new JsonResponse([
			"heading" => Loc::getMessage("AI0_DW_DELUXE_API_ERROR_TITLE"),
			"errors" => [
				Loc::getMessage("AI0_DW_DELUXE_API_REQUEST_PRICE_ERROR_PHONE_REQUIRED")
			],
			"success" => false
		]);
		$response
			->setStatus(422)
			->send();

		$application->terminate();
	}

	if($productId === null) {
		$response = new JsonResponse([
			"heading" => Loc::getMessage("AI0_DW_DELUXE_API_ERROR_TITLE"),
			"errors" => [
				Loc::getMessage("AI0_DW_DELUXE_API_REQUEST_PRICE_ERROR_PRODUCT_REQUIRED")
			],
			"success" => false
		]);
		$response
			->setStatus(422)
			->send();

		$application->terminate();
	}

	$productId = (int) $productId;

	$element = ElementTable::getList([
		'filter' => ['ID' => $productId],
		'select' => ['ID', 'NAME'],
		'limit' => 1
	])->fetch();

	if(!$element) {
		$response = new JsonResponse([
			"heading" => Loc::getMessage("AI0_DW_DELUXE_API_ERROR_TITLE"),
			"errors" => [
				Loc::getMessage("AI0_DW_DELUXE_API_REQUEST_PRICE_ERROR_PRODUCT_NOT_FOUND")
			],
			"success" => false
		]);
		$response
			->setStatus(404)
			->send();
		$application->terminate();
	}

	$eventType = \CEventType::GetList([
		"LID" => LANGUAGE_ID,
		"TYPE_ID" => "SALE_DRESSCODE_REQUEST_SEND"
	])->Fetch();

	if(!$eventType) {
		$eventType = new \CEventType;
		$eventType->Add([
			'LID' => LANGUAGE_ID,
			'EVENT_NAME' => 'SALE_DRESSCODE_REQUEST_SEND',
			'NAME' => Loc::getMessage("AI0_DW_DELUXE_API_REQUEST_PRICE_EVENT_NAME"),
			'DESCRIPTION' => "#SITE# \n #PRODUCT# \n #NAME# \n #PHONE# \n #COMMENT# \n"
		]);
	}

	$eventMessage = \CEventMessage::GetList(
		$by = "site_id",
		$order = "desc",
		[
			"TYPE" => "SALE_DRESSCODE_REQUEST_SEND"
		]
	)->Fetch();

	if(!$eventMessage) {
		$message = Loc::getMessage("AI0_DW_DELUXE_API_REQUEST_PRICE_EMAIL_TEMPLATE");

		$eventMessage = new \CEventMessage;
		$eventMessage->Add([
			'ACTIVE' => 'Y',
			'EVENT_NAME' => 'SALE_DRESSCODE_REQUEST_SEND',
			'LID' => SITE_ID,
			'EMAIL_FROM' => Option::get('main', 'email_from'),
			'EMAIL_TO' => Option::get('sale', 'order_email'),
			'BCC' => Option::get('main', 'email_from'),
			'SUBJECT' => Loc::getMessage("AI0_DW_DELUXE_API_REQUEST_PRICE_EMAIL_SUBJECT"),
			'BODY_TYPE' => 'html',
			'MESSAGE' => $message
		]);
	}

	$productInfo = "{$element['NAME']} (ID: {$element['ID']})";

	$arMessage = [
		"SITE" => $serverName,
		"PRODUCT" => $productInfo,
		"NAME" => \htmlspecialcharsbx($name),
		"PHONE" => \htmlspecialcharsbx($telephone),
		"COMMENT" => \htmlspecialcharsbx($comment)
	];

	Event::send([
		"EVENT_NAME" => "SALE_DRESSCODE_REQUEST_SEND",
		"LID" => SITE_ID,
		"C_FIELDS" => $arMessage,
		"DUPLICATE" => "Y",
		"MESSAGE_ID" => null,
		'LANGUAGE_ID' => LANGUAGE_ID,
	]);

	if ($userAgreementId !== null) {
		Consent::addByContext($userAgreementId);
	}

	$response = new JsonResponse(["success" => true]);
	$response
		->setStatus(200)
		->send();

	$application->terminate();
}
elseif($actionName == "getProductQuickBuyForm"){
	$settings = \DwSettings::getInstance()->getCurrentSettings();

	$usePhoneMask = $settings['TEMPLATE_USE_MASKED_INPUT'] ?? null;
	$phoneMaskFormat = $settings['MASKED_INPUT_CUSTOM_FORMAT'] ?? null;

	$productId = $_GET['productId'] ?? null;

	if($productId === null) {
		$response = new JsonResponse(["errors" => ["Product id parameter is required"]]);
		$response
			->setStatus(400)
			->send();

		$application->terminate();
	}

	$response = new Component(
		'dresscode:product.quick.buy.form',
		'modal',
		[
			"PRODUCT_ID" => $productId,
			"USE_PHONE_MASK" => $usePhoneMask,
			"PHONE_MASK_FORMAT" => $phoneMaskFormat,
			"CACHE_TYPE" => "A",
		]
	);
	$response->send();

	$application->terminate();
}
elseif($actionName === "productQuickBuy"){
	if (!check_bitrix_sessid()) {
		$response = new JsonResponse([
			"errors" => [Loc::getMessage("AI0_DW_DELUXE_API_CSRF_ERROR")],
			"success" => false
		]);
		$response->setStatus(403)->send();
		$application->terminate();
	}

	$settings = \DwSettings::getInstance()->getCurrentSettings();

	$userAgreementId = $settings['TEMPLATE_AGREEMENT_ID'] ?? null;

	$productId = $_POST["productId"] ?? null;
	$name = $_POST["name"] ?? null;
	$telephone = $_POST["telephone"] ?? null;
	$email = $_POST["email"] ?? null;
	$comment = $_POST["comment"] ?? null;

	if($telephone === null) {
		$response = new JsonResponse([
			"heading" => Loc::getMessage("AI0_DW_DELUXE_API_ERROR_TITLE"),
			"errors" => [
				Loc::getMessage("AI0_DW_DELUXE_API_PRODUCT_QUICK_BUY_ERROR_PHONE_REQUIRED")
			],
			"success" => false
		]);
		$response
			->setStatus(422)
			->send();

		$application->terminate();
	}

	if($productId === null) {
		$response = new JsonResponse([
			"heading" => Loc::getMessage("AI0_DW_DELUXE_API_ERROR_TITLE"),
			"errors" => [
				Loc::getMessage("AI0_DW_DELUXE_API_PRODUCT_QUICK_BUY_ERROR_PRODUCT_REQUIRED")
			],
			"success" => false
		]);
		$response
			->setStatus(422)
			->send();

		$application->terminate();
	}

	$productId = (int) $productId;

	$product = ElementTable::getList([
		'filter' => ['ID' => $productId],
		'select' => ['ID', 'XML_ID', 'IBLOCK_XML_ID' => 'IBLOCK.XML_ID'],
		'limit' => 1
	])->fetch();

	if(!$product) {
		$response = new JsonResponse([
			"heading" => Loc::getMessage("AI0_DW_DELUXE_API_ERROR_TITLE"),
			"errors" => [
				Loc::getMessage("AI0_DW_DELUXE_API_PRODUCT_QUICK_BUY_ERROR_PRODUCT_NOT_FOUND")
			],
			"success" => false
		]);
		$response
			->setStatus(404)
			->send();

		$application->terminate();
	}

	$basket = Basket::create(SITE_ID);

	$basketItem = $basket->createItem('catalog', $productId);
	$basketItem->setFields([
		'QUANTITY' => 1,
		'CURRENCY' => $baseCurrency,
		'LID' => SITE_ID,
		'PRODUCT_PROVIDER_CLASS' => CatalogProvider::class,
		'CATALOG_XML_ID' => $product["IBLOCK_XML_ID"],
		'PRODUCT_XML_ID' => $product["XML_ID"],
	]);

	$personTypes = PersonType::load(SITE_ID);
	if(!is_array($personTypes) || $personTypes === []) {
		$response = new JsonResponse([
			"heading" => Loc::getMessage("AI0_DW_DELUXE_API_ERROR_TITLE"),
			"errors" => [
				Loc::getMessage("AI0_DW_DELUXE_API_PRODUCT_QUICK_BUY_ERROR_NO_PERSON_TYPE")
			],
			"success" => false
		]);
		$response
			->setStatus(500)
			->send();

		$application->terminate();
	}

	$activePersonTypes = array_filter($personTypes, fn($type) => $type['ACTIVE'] === 'Y');
	$firstActivePersonType = reset($activePersonTypes);
	$personTypeId = $firstActivePersonType['ID'] ?? null;

	if($personTypeId === null) {
		$response = new JsonResponse([
			"heading" => Loc::getMessage("AI0_DW_DELUXE_API_ERROR_TITLE"),
			"errors" => [
				Loc::getMessage("AI0_DW_DELUXE_API_PRODUCT_QUICK_BUY_ERROR_NO_ACTIVE_PERSON_TYPE")
			],
			"success" => false
		]);
		$response
			->setStatus(500)
			->send();

		$application->terminate();
	}

	$userId = $USER->GetID();
	if(!$userId) {
		$userId = \CSaleUser::GetAnonymousUserID();
	}

	$order = Order::create(SITE_ID, $userId);
	$order->setPersonTypeId($personTypeId);
	$order->setBasket($basket);

	$properties = $order->getPropertyCollection();

	$properties->getPhone()?->setValue($telephone);

	if($name !== null) {
		$properties->getPayerName()?->setValue($name);
	}

	if($email !== null) {
		$properties->getUserEmail()?->setValue($email);
	}

	if($comment !== null) {
		$order->setField('USER_DESCRIPTION', $comment);
	}

	$shipmentCollection = $order->getShipmentCollection();
	$shipment = $shipmentCollection->createItem();
	$shipmentItemCollection = $shipment->getShipmentItemCollection();

	foreach($basket as $basketItem) {
		$shipmentItem = $shipmentItemCollection->createItem($basketItem);
		$shipmentItem->setQuantity($basketItem->getQuantity());
	}

	$order->doFinalAction(true);

	$result = $order->save();

	if(!$result->isSuccess()) {
		$response = new JsonResponse([
			"heading" => Loc::getMessage("AI0_DW_DELUXE_API_PRODUCT_QUICK_BUY_ERROR_ORDER_CREATE"),
			"errors" => $result->getErrorMessages(),
			"success" => false
		]);
		$response
			->setStatus(500)
			->send();

		$application->terminate();
	}

	if ($userAgreementId !== null) {
		Consent::addByContext($userAgreementId);
	}

	$orderId = $order->getId();
	$orderNumber = $order->getField('ACCOUNT_NUMBER');

	$response = new JsonResponse([
		"success" => true,
		"orderId" => $orderId,
		"orderNumber" => $orderNumber
	]);
	$response->send();

	$application->terminate();
}
elseif($actionName == "getAvailableWindow"){
	if(!empty($_GET["product_id"])){
		ob_start();

		$APPLICATION->IncludeComponent(
			"bitrix:catalog.store.amount",
			"fastView",
			array(
				"COMPONENT_TEMPLATE" => "fastView",
				"ELEMENT_ID" => intval($_GET["product_id"]),
				"STORES" => array(),
				"ELEMENT_CODE" => "",
				"YANDEX_MAP_VERSION" => "2.0",
				"STORE_PATH" => "/stores/#store_id#/",
				"CACHE_TYPE" => "N",
				"CACHE_TIME" => "36000000",
				"MAIN_TITLE" => "",
				"USER_FIELDS" => array(
					0 => "",
					1 => "",
				),
				"FIELDS" => array(
					0 => "TITLE",
					1 => "ADDRESS",
					2 => "DESCRIPTION",
					3 => "PHONE",
					4 => "EMAIL",
					5 => "IMAGE_ID",
					6 => "COORDINATES",
					7 => "SCHEDULE",
					8 => "",
				),
				"SHOW_EMPTY_STORE" => "Y",
				"USE_MIN_AMOUNT" => "Y",
				"SHOW_GENERAL_STORE_INFORMATION" => "N",
				"MIN_AMOUNT" => "0"
			),
			false,
			array("HIDE_ICONS" => "Y")
		);

		$componentData = ob_get_contents();
		ob_end_clean();

		echo Json::encode(
			array(
				"SUCCESS" => "Y",
				"COMPONENT_DATA" => $componentData
			)
		);
	}
}

elseif ($actionName == "addSubscribe") {
	if (!check_bitrix_sessid()) {
		$response = new JsonResponse(["errors" => ["Неверный токен сессии"]]);
		$response->setStatus(403)->send();
		$application->terminate();
	}

	$id = (int)($request->getPost("id") ?? 0);
	$siteId = htmlspecialcharsbx($request->getPost("site_id") ?? '');

	if ($id <= 0 || empty($siteId)) {
		$response = new JsonResponse(["errors" => ["Не передан ID товара или ID сайта"]]);
		$response->setStatus(400)->send();
		$application->terminate();
	}

	$userId = false;
	if ($USER && is_object($USER) && $USER->isAuthorized()) {
		$userId = $USER->getId();
	}

	$subscribeExists = SubscribeTable::getList([
		"select" => ["ID"],
		"filter" => [
			"USER_CONTACT" => $USER->getEmail(),
			"ITEM_ID" => $id,
			"SITE_ID" => $siteId,
			"USER_ID" => $userId,
		],
	])->fetch();

	if (!$subscribeExists) {
		ob_start();
		$APPLICATION->IncludeComponent(
			"dresscode:catalog.product.subscribe",
			".default",
			[
				"SITE_ID" => $siteId,
				"PRODUCT_ID" => $id,
			],
			false,
			["HIDE_ICONS" => "Y"]
		);
		$componentData = ob_get_clean();

		$response = new JsonResponse([
			"SUCCESS" => "Y",
			"SUBSCRIBE_FORM" => $componentData,
		]);
		$response->send();
		$application->terminate();
	} else {
		$response = new JsonResponse([
			"ERROR" => "Y",
			"SUBSCRIBE" => "IS_EXIST",
			"message" => "Вы уже подписаны на этот товар"
		]);
		$response->send();
		$application->terminate();
	}
}

elseif ($actionName == "unSubscribe") {
	if (!check_bitrix_sessid()) {
		$response = new JsonResponse(["errors" => ["Неверный токен сессии"]]);
		$response->setStatus(403)->send();
		$application->terminate();
	}

	$subscribeId = (int)($request->getPost("subscribeId") ?? 0);
	if ($subscribeId <= 0) {
		$response = new JsonResponse(["errors" => ["Не передан ID подписки"]]);
		$response->setStatus(400)->send();
		$application->terminate();
	}

	$subscription = SubscribeTable::getList([
		"select" => ["ID", "ITEM_ID", "USER_CONTACT"],
		"filter" => ["ID" => $subscribeId],
	])->fetch();

	if (!$subscription) {
		$response = new JsonResponse(["ERROR" => "Y", "message" => "Подписка не найдена"]);
		$response->send();
		$application->terminate();
	}

	$subscribeManager = new SubscribeManager();
	$unsubscribeResult = $subscribeManager->unSubscribe([
		"unSubscribe" => "Y",
		"subscribeId" => $subscription["ID"],
		"productId" => $subscription["ITEM_ID"],
		"userContact" => $subscription["USER_CONTACT"],
	]);

	if ($unsubscribeResult) {
		$response = new JsonResponse(["SUCCESS" => "Y"]);
		$response->send();
		$application->terminate();
	} else {
		$errors = $subscribeManager->getErrors();
		$errorMessage = $errors ? current($errors)->getMessage() : "Ошибка при отписке";
		$response = new JsonResponse(["ERROR" => "Y", "message" => $errorMessage]);
		$response->send();
		$application->terminate();
	}
}

elseif($actionName == "getPricesWindow"){
	if(!empty($_GET["product_id"])){
		$APPLICATION->IncludeComponent(
			"dresscode:catalog.prices.view",
			".default",
			array(
				"COMPONENT_TEMPLATE" => ".default",
				"CACHE_TYPE" => "A",
				"CACHE_TIME" => "360000",
				"PRODUCT_ID" => intval($_GET["product_id"]),
				"PRODUCT_PRICE_CODE" => explode("||", $_GET["product_price_code"]),
				"CURRENCY_ID" => $_GET["product_currency"]
			),
			false
		);
	}
}elseif($actionName == "getFastView"){
	if(!empty($_GET["product_id"])){
		$APPLICATION->IncludeComponent(
			"dresscode:catalog.item",
			"fast",
			array(
				"COMPONENT_TEMPLATE" => ".default",
				"CACHE_TIME" => "36000000",
				"CACHE_TYPE" => "Y",
				"DISPLAY_MORE_PICTURES" => "Y",
				"DISPLAY_LAST_SECTION" =>  "N",
				"DISPLAY_FILES_VIDEO" =>  "N",
				"DISPLAY_RELATED" => "N",
				"DISPLAY_SIMILAR" =>  "N",
				"DISPLAY_BRAND" =>  "Y",
				"PICTURE_HEIGHT" => "",
				"PICTURE_WIDTH" => "",
				"GET_MORE_PICTURES" => "Y", // more picture + detail picture
				"IBLOCK_ID" => intval($_GET["product_iblock_id"]),
				"PRODUCT_ID" => intval($_GET["product_id"]),
				"CURRENCY_ID" => $_GET["product_currency_id"],
				"HIDE_MEASURES" => $_GET["product_hide_measures"],
				"CONVERT_CURRENCY" => $_GET["product_convert_currency"],
				"HIDE_NOT_AVAILABLE" => $_GET["product_hide_not_available"],
				"PRODUCT_PRICE_CODE" => !empty($_GET["product_price_code"]) ? explode("||", $_GET["product_price_code"]) : NUll
			),
			false
		);
	}
}
elseif($actionName == "selectSku"){
	if(!empty($_GET["params"]) &&
		!empty($_GET["iblock_id"]) &&
		!empty($_GET["prop_id"]) &&
		!empty($_GET["product_id"]) &&
		!empty($_GET["level"]) &&
		!empty($_GET["props"])
	){

		$OPTION_ADD_CART = \COption::GetOptionString("catalog", "default_can_buy_zero");
		$OPTION_CURRENCY  = \Bitrix\Sale\Internals\SiteCurrencyTable::getSiteCurrency(SITE_ID);

		$arResult["PRODUCT_PRICE_ALLOW"] = array();
		$arResult["PRODUCT_PRICE_ALLOW_FILTER"] = array();
		$arPriceCode = array();

		if(!empty($_GET["price-code"]) && $_GET["price-code"] != "undefined"){
			$arPriceCode = explode("||", $_GET["price-code"]);
			$dbPriceType = \CCatalogGroup::GetList(
				array("SORT" => "ASC"),
				array("NAME" => $arPriceCode)
			);
			while ($arPriceType = $dbPriceType->Fetch()){
				if($arPriceType["CAN_BUY"] == "Y")
					$arResult["PRODUCT_PRICE_ALLOW"][] = $arPriceType;
				$arResult["PRODUCT_PRICE_ALLOW_FILTER"][] = $arPriceType["ID"];
			}
		}

		$arTmpFilter = array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => intval($_GET["iblock_id"]),
			"PROPERTY_".intval($_GET["prop_id"]) => intval($_GET["product_id"])
		);

		$arProps = array();
		$arParams =  array();
		$arTmpParams = array();
		$arCastFilter = array();
		$arProperties = array();
		$arPropActive = array();
		$arAllProperties = array();
		$arPropertyTypes = array();
		$arPropCombination = array();
		$arHighloadProperty = array();

		$PROPS = $_GET["props"];
		$PARAMS = $_GET["params"];
		$HIGHLOAD = $_GET["highload"];

		//normalize property
		$exProps = explode(";", trim($PROPS, ";"));
		$exParams = explode(";", trim($PARAMS, ";"));
		$exHighload = explode(";", trim($HIGHLOAD, ";"));

		if(empty($exProps) || empty($exParams))
			die("error #1 | Empty params or propList _no valid data");

		if(!empty($exHighload)){
			foreach ($exHighload as $ihl => $nextHighLoad) {
				$arHighloadProperty[$nextHighLoad] = "Y";
			}
		}

		foreach ($exProps as $ip => $sProp) {
			$msp = explode(":", $sProp);
			$arProps[$msp[0]][$msp[1]] = "D";
		}

		foreach ($exParams as $ip => $pProp) {
			$msr = explode(":", $pProp);
			$arParams[$msr[0]] = $msr[1];
			$resProp = \CIBlockProperty::GetByID($msr[0]);
			if($arNextPropGet = $resProp->GetNext()){
				$arPropertyTypes[$msr[0]] = $arNextPropGet["PROPERTY_TYPE"];
				if(empty($arHighloadProperty[$msr[0]]) && $arNextPropGet["PROPERTY_TYPE"] != "E"){
					$arTmpParams["PROPERTY_".$msr[0]."_VALUE"] = $msr[1];
				}else{
					$arTmpParams["PROPERTY_".$msr[0]] = $msr[1];
				}
			}
		}

		$arFilter = array_merge($arTmpFilter, array_slice($arTmpParams, 0, $_GET["level"]));

		$rsOffer = \CIBlockElement::GetList(
			array(),
			$arFilter, false, false,
			array(
				"ID",
				"NAME",
				"IBLOCK_ID",
				"CATALOG_MEASURE",
				"CATALOG_AVAILABLE",
				"CATALOG_QUANTITY",
				"CATALOG_QUANTITY_TRACE",
				"CATALOG_CAN_BUY_ZERO"
			)
		);

		while($obOffer = $rsOffer->GetNextElement()){

			$arOfferParams = $obOffer->GetFields();
			$arFilterProp = $obOffer->GetProperties();

			foreach ($arFilterProp as $ifp => $arNextProp) {

				if(empty($arNextProp["VALUE"]) || $arNextProp["MULTIPLE"] === "Y") {
					continue;
				}

				if(
					$arNextProp["PROPERTY_TYPE"] == "L"
					|| $arNextProp["PROPERTY_TYPE"] == "E"
					|| $arNextProp["PROPERTY_TYPE"] == "S" && !empty($arNextProp["USER_TYPE_SETTINGS"]["TABLE_NAME"])
				){
					$arProps[$arNextProp["CODE"]][$arNextProp["VALUE"]] = "N";
					$arProperties[$arNextProp["CODE"]] = $arNextProp["VALUE"];
					$arPropCombination[$arOfferParams["ID"]][$arNextProp["CODE"]][$arNextProp["VALUE"]] = "Y";
				}

			}

		}


		if(!empty($arParams)){
			foreach ($arParams as $propCode => $arField) {
				if($arProps[$propCode][$arField] == "N"){
					$arProps[$propCode][$arField] = "Y";
				}else{
					if(!empty($arProps[$propCode])){
						foreach ($arProps[$propCode] as $iCode => $upProp) {
							if($upProp == "N"){
								$arProps[$propCode][$iCode] = "Y";
								break;
							}
						}
					}
				}
			}
		}

		if(!empty($arProps)){
			foreach ($arProps as $ip => $arNextProp) {
				foreach ($arNextProp as $inv => $arNextPropValue) {
					if($arNextPropValue == "Y"){
						$arPropActive[$ip] = $inv;
						$arPropActiveIndex[$activeIntertion++] = $inv;
					}
				}
			}
		}

		if(!empty($arProps)){
			$arPrevLevelProp = array();
			$levelIteraion = 0;
			foreach ($arProps as $inp => $arNextProp){ //level each
				if($levelIteraion > 0){
					foreach ($arNextProp as $inpp => $arNextPropEach) {
						if($arNextPropEach == "N" && !empty($arPrevLevelProp)){
							$seachSuccess = false;
							foreach ($arPropCombination as $inc => $arNextCombination) {
								if($arNextCombination[$inp][$inpp] == "Y" && $arNextCombination[$arPrevLevelProp["INDEX"]][$arPrevLevelProp["VALUE"]] == "Y"){
									$seachSuccess = true;
									break;
								}
							}
							if($seachSuccess == false){
								$arProps[$inp][$inpp] = "D";
							}
						}
					}
				}$levelIteraion++;
				$arPrevLevelProp = array("INDEX" => $inp, "VALUE" => $arPropActive[$inp]);
			}
		}

		$arLastFilter = array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => intval($_GET["iblock_id"]),
			"PROPERTY_".intval($_GET["prop_id"]) => intval($_GET["product_id"])
		);

		foreach ($arPropActive as $icp => $arNextProp) {
			if(empty($arHighloadProperty[$icp]) && $arPropertyTypes[$icp] != "E"){
				$arLastFilter["PROPERTY_".$icp."_VALUE"] = $arNextProp;
			}else{
				$arLastFilter["PROPERTY_".$icp] = $arNextProp;
			}
		}

		$arSkuPriceCodes = array();

		if(!empty($arResult["PRODUCT_PRICE_ALLOW"])){
			$arSkuPriceCodes["PRODUCT_PRICE_ALLOW"] = $arResult["PRODUCT_PRICE_ALLOW"];
		}

		if(!empty($arPriceCode)){
			$arSkuPriceCodes["PARAMS_PRICE_CODE"] = $arPriceCode;
		}

		$arLastOffer = getLastOffer($arLastFilter, $arProps, $_GET["product_id"], $OPTION_CURRENCY, !empty($_GET["product-more-pictures"]), $arSkuPriceCodes);

		if(!empty($arLastOffer["PRODUCT"]["CATALOG_MEASURE"])){

			$rsMeasure = \CCatalogMeasure::getList(
				array(),
				array(
					"ID" => $arLastOffer["PRODUCT"]["CATALOG_MEASURE"]
				),
				false,
				false
			);

			while($arNextMeasure = $rsMeasure->Fetch()) {
				$arLastOffer["PRODUCT"]["MEASURE"] = $arNextMeasure;
			}
		}

		if(!empty($_GET["product-change-prop"]) && $_GET["product-change-prop"] != "undefined"){
			ob_start();
			$APPLICATION->IncludeComponent(
				"dresscode:catalog.properties.list",
				htmlspecialchars($_GET["product-change-prop"]),
				array(
					"PRODUCT_ID" => $arLastOffer["PRODUCT"]["ID"],
					"COUNT_PROPERTIES" => 10
				),
				false
			);
			$arLastOffer["PRODUCT"]["RESULT_PROPERTIES"] = ob_get_contents();
			ob_end_clean();
		}

		//price count
		$arPriceFilter = array("PRODUCT_ID" => $arLastOffer["PRODUCT"]["ID"], "CAN_ACCESS" => "Y");
		if(!empty($arResult["PRODUCT_PRICE_ALLOW_FILTER"])){
			$arPriceFilter["CATALOG_GROUP_ID"] = $arResult["PRODUCT_PRICE_ALLOW_FILTER"];
		}
		$dbPrice = \CPrice::GetList(
			array(),
			$arPriceFilter,
			false,
			false,
			array("ID")
		);
		$arLastOffer["PRODUCT"]["COUNT_PRICES"] = $dbPrice->SelectedRowsCount();

		//Информация о складах
		$rsStore = \CCatalogStoreProduct::GetList(array(), array("PRODUCT_ID" => $arLastOffer["PRODUCT"]["ID"]), false, false, array("ID", "AMOUNT"));
		while($arNextStore = $rsStore->GetNext()){
			$arLastOffer["PRODUCT"]["STORES"][] = $arNextStore;
		}

		$arLastOffer["PRODUCT"]["STORES_COUNT"] = !empty($arLastOffer["PRODUCT"]["STORES"]) ? count($arLastOffer["PRODUCT"]["STORES"]) : 0;

		//measure ratio
		$rsMeasureRatio = \CCatalogMeasureRatio::getList(
			array(),
			array("PRODUCT_ID" => $arLastOffer["PRODUCT"]["ID"]),
			false,
			false,
			array()
		);

		if($arMeasureRatio = $rsMeasureRatio->Fetch()){
			if(!empty($arMeasureRatio["RATIO"])){
				$measureRatio = $arMeasureRatio["RATIO"];
			}
		}

		//set measure ration
		$arLastOffer["PRODUCT"]["BASKET_STEP"] = !empty($measureRatio) ? $measureRatio : 1;

		//push values
		if(!empty($arProps)){
			echo Json::encode(
				array(
					array("PRODUCT" => $arLastOffer["PRODUCT"]),
					array("PROPERTIES" => $arLastOffer["PROPERTIES"])
				)
			);
		}

	}
}

elseif ($actionName == "addCart") {
    if (!check_bitrix_sessid()) {
        $response = new JsonResponse(["errors" => ["Неверный токен сессии"]]);
        $response->setStatus(403)->send();
        $application->terminate();
    }

    $multi = $request->getPost("multi");
    $id = $request->getPost("id");
    $q = $request->getPost("q");
    $hideMeasures = \htmlspecialcharsbx($request->getPost("hide_measures") ?? '');

    if ($multi == "1") {
        $errors = [];

        $addElements = explode(";", $id);
        $addQuantity = !empty($q) ? explode(";", $q) : [];
        $elementsQuantity = [];

        if (!empty($addQuantity)) {
            foreach ($addQuantity as $nextQuantity) {
                $exQuantity = explode(":", $nextQuantity);

                if (!empty($exQuantity[0]) && !empty($exQuantity[1])) {
                    $elementsQuantity[$exQuantity[0]] = $exQuantity[1];
                }
            }
        }

        foreach ($addElements as $nextId) {
            $nextId = (float)$nextId;
            $quantity = 1;

            if (isset($elementsQuantity[$nextId])) {
                $quantity = $elementsQuantity[$nextId];
            } else {
                $rsMeasureRatio = \CCatalogMeasureRatio::getList([], ["PRODUCT_ID" => $nextId]);

                if ($arProductMeasureRatio = $rsMeasureRatio->Fetch()) {
                    if (!empty($arProductMeasureRatio["RATIO"])) {
                        $quantity = $arProductMeasureRatio["RATIO"];
                    }
                }
            }
            $basketResult = Bitrix\Catalog\Product\Basket::addProduct([
                "PRODUCT_ID" => $nextId,
                "QUANTITY" => $quantity,
                "PROPS" => [],
            ]);

            if (!$basketResult->isSuccess()) {
                $errors[$nextId] = $basketResult->getErrorMessages();
            }
        }
        if (!empty($errors)) {
            $response = new JsonResponse(["errors" => $errors, "status" => false]);
            $response->send();
            $application->terminate();
        } else {
            $response = new JsonResponse(["status" => true]);
            $response->send();
            $application->terminate();
        }
    } else {
        $id = (int)$id;
        $quantity = (float)$q;

        if ($id <= 0) {
            $response = new JsonResponse(["errors" => ["Неверный ID товара"]]);
            $response->setStatus(400)->send();
            $application->terminate();
        }

        $rsMeasureRatio = \CCatalogMeasureRatio::getList([], ["PRODUCT_ID" => $id]);
        $arProductMeasureRatio = $rsMeasureRatio->Fetch();

        $addBasketQuantity = (!empty($arProductMeasureRatio["RATIO"])) ? $arProductMeasureRatio["RATIO"] : 1;
        if ($quantity > 0 && $quantity != $addBasketQuantity) {
            $addBasketQuantity = $quantity;
        }

        $basketResult = Bitrix\Catalog\Product\Basket::addProduct([
            "PRODUCT_ID" => $id,
            "QUANTITY" => $addBasketQuantity,
            "PROPS" => [],
        ]);

        if (!$basketResult->isSuccess()) {
            $response = new JsonResponse(["errors" => $basketResult->getErrorMessages(), "status" => false]);
            $response->send();
            $application->terminate();
        } else {
            ob_start();
            $APPLICATION->IncludeComponent(
                "dresscode:sale.basket.window",
                ".default",
                [
                    "HIDE_MEASURES" => $hideMeasures,
                    "PRODUCT_ID" => $id,
                    "SITE_ID" => $siteId,
                ],
                false
            );
            $componentHTML = ob_get_clean();
            $response = new JsonResponse(["window_component" => $componentHTML, "status" => true]);
            $response->send();
            $application->terminate();
        }
    }
}

elseif($actionName == "del"){
	if (!check_bitrix_sessid()) {
		$response = new JsonResponse(["errors" => ["Неверный токен сессии"]]);
		$response->setStatus(403)->send();
		$application->terminate();
	}

	$id = (int)($_POST["id"] ?? 0);

	if ($id > 0) {
		echo \CSaleBasket::Delete($id);
	} else {
		echo 0;
	}
}

elseif($actionName == "upd"){
	if (!check_bitrix_sessid()) {
		$response = new JsonResponse(["errors" => ["Неверный токен сессии"]]);
		$response->setStatus(403)->send();
		$application->terminate();
	}

	if(!empty($_GET["id"])){
		//vars
		$arReturn = array();

		$getList = \CIBlockElement::GetList(
			Array(),
			array(
				"ID" => intval($_GET['id'])
			),
			false,
			false,
			array(
				"ID",
				"NAME",
				"DETAIL_PICTURE",
				"DETAIL_PAGE_URL",
				"CATALOG_MEASURE",
				"CATALOG_AVAILABLE",
				"CATALOG_QUANTITY",
				"CATALOG_QUANTITY_TRACE",
				"CATALOG_CAN_BUY_ZERO"
			)
		);

		$obj = $getList->GetNextElement();
		$arProduct = $obj->GetFields();

		$OPTION_QUANTITY_TRACE = $arProduct["CATALOG_QUANTITY_TRACE"];

		if(!empty($arProduct)){
			$dbBasketItems = \CSaleBasket::GetList(
				false,
				array(
					"FUSER_ID" => \CSaleBasket::GetBasketUserID(),
					"ORDER_ID" => "NULL",
					"PRODUCT_ID" => intval($_GET["id"]),
					"LID" => $_GET["site_id"],
				),
				false,
				false,
				array("ID")
			);

			$basketRES = $dbBasketItems->Fetch();
			if(!empty($basketRES)){

				if($OPTION_QUANTITY_TRACE == "Y"){
					if($arProduct["CATALOG_QUANTITY"] < doubleval($_GET["q"])){
						$quantityError = true;
					}
				}

				if(!$quantityError){

					if(\CSaleBasket::Update($basketRES["ID"], array("QUANTITY" => doubleval($_GET["q"])))){

						//extented prices and rules for working with basket
						$dbBasketItems = \CSaleBasket::GetList(
							false,
							array(
								"FUSER_ID" => \CSaleBasket::GetBasketUserID(),
								"PRODUCT_ID" => intval($_GET["id"]),
								"LID" => $_GET["site_id"],
								"ORDER_ID" => "NULL"
							),
							false,
							false,
							array(
								"ID",
								"QUANTITY",
								"PRICE",
								"PRODUCT_ID",
								"CURRENCY",
								"DISCOUNT_PRICE",
								"MODULE"
							)
						);

						$basketQty = $dbBasketItems->Fetch();

						$allSum += ($basketQty["PRICE"] * $basketQty["QUANTITY"]);
						$allWeight += ($basketQty["WEIGHT"] * $basketQty["QUANTITY"]);
						$arItems[] = $basketQty;

						$arOrder = array(
							"SITE_ID" => $_GET["site_id"],
							"USER_ID" => $USER->GetID(),
							"ORDER_PRICE" => $allSum,
							"ORDER_WEIGHT" => $allWeight,
							"BASKET_ITEMS" => $arItems
						);

						$arOptions = array(
							"COUNT_DISCOUNT_4_ALL_QUANTITY" => "Y",
						);

						$arErrors = array();

						\CSaleDiscount::DoProcessOrder($arOrder, $arOptions, $arErrors);
						$basketQty = $arOrder["BASKET_ITEMS"][0];

						$basketQty["~DISCOUNT_PRICE"] = !empty($basketQty["DISCOUNT_PRICE"]) && $basketQty["DISCOUNT_PRICE"] > 0 ? CCurrencyLang::CurrencyFormat($basketQty["PRICE"] + $basketQty["DISCOUNT_PRICE"], $basketQty["CURRENCY"], true) : $basketQty["DISCOUNT_PRICE"];
						$basketQty["DISCOUNT_SUM"] = !empty($basketQty["DISCOUNT_PRICE"]) && $basketQty["DISCOUNT_PRICE"] > 0 ? CCurrencyLang::CurrencyFormat(($basketQty["PRICE"] + $basketQty["DISCOUNT_PRICE"]) * round($basketQty["QUANTITY"]), $basketQty["CURRENCY"], true) : $basketQty["DISCOUNT_PRICE"];
						$basketQty["OLD_PRICE"] = round($basketQty["~DISCOUNT_PRICE"]) > 0 ? $basketQty["PRICE"] + $basketQty["DISCOUNT_PRICE"] : 0;
						$arProduct["CAN_BUY"] = $arProduct["CATALOG_AVAILABLE"];
						$arProduct["MEASURE_SYMBOL_RUS"] = "";

						if(!empty($arProduct["CATALOG_MEASURE"])){

							$rsMeasure = \CCatalogMeasure::getList(
								array(),
								array(
									"ID" => $arProduct["CATALOG_MEASURE"]
								),
								false,
								false
							);

							while($arNextMeasure = $rsMeasure->Fetch()) {
								$arProduct["MEASURE"] = $arNextMeasure;
							}
						}

						if(!empty($arProduct["MEASURE"])){
							$arProduct["MEASURE_SYMBOL_RUS"] = $arProduct["MEASURE"]["SYMBOL_RUS"];
						}

						//write data
						$arReturn = array(
							"PRODUCT_ID" => intval($basketQty["PRODUCT_ID"]),
							"~PRICE" => round($basketQty["PRICE"]),
							"OLD_PRICE" => $basketQty["OLD_PRICE"],
							"SUM" => addslashes(\CCurrencyLang::CurrencyFormat(round($basketQty["PRICE"]) * doubleval($basketQty["QUANTITY"]), $basketQty["CURRENCY"], true)),
							"PRICE" => addslashes(\CCurrencyLang::CurrencyFormat($basketQty["PRICE"], $basketQty["CURRENCY"], true)),
							"DISCOUNT_PRICE" => $basketQty["~DISCOUNT_PRICE"],
							"DISCOUNT_SUM" => $basketQty["DISCOUNT_SUM"],
							"CAN_BUY" => $arProduct["CAN_BUY"],
							"MEASURE_SYMBOL_RUS" => $arProduct["MEASURE_SYMBOL_RUS"]
						);

						//success flag
						$arReturn["success"] = "Y";

						//return data
						echo Json::encode($arReturn);

					}

					else{
						echo '{"error" : "basketUpdateError"}';
					}

				}else{
					\CSaleBasket::Update($basketRES["ID"], array("QUANTITY" => $arProduct["CATALOG_QUANTITY"]));
					echo '{"error" : "quantityError", "currentQuantityValue": "'.$arProduct["CATALOG_QUANTITY"].'"}';
				}

			}else{
				echo '{"error" : "productCartError"}';
			}
		}else{
			echo '{"error" : "productNotFoundError"}';
		}
	}else{
		echo '{"error" : "empty product id"}';
	}
}
elseif($actionName == "skuADD"){
	if(!empty($_GET["id"]) && !empty($_GET["ibl"])){

		$PRODUCT_ID = intval($_GET["id"]);
		$IBLOCK_ID  = intval($_GET["ibl"]);
		$SKU_INFO   = \CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);
		$PRODUCT_INFO = \CIBlockElement::GetByID($PRODUCT_ID)->GetNext();
		$OPTION_ADD_CART  = \COption::GetOptionString("catalog", "default_can_buy_zero");
		$OPTION_CURRENCY  = \CCurrency::GetBaseCurrency();

		$dbPriceType = \CCatalogGroup::GetList(
			array("SORT" => "ASC"),
			array("BASE" => "Y")
		);

		while ($arPriceType = $dbPriceType->Fetch()){
			$OPTION_BASE_PRICE = $arPriceType["ID"];
		}

		if (is_array($SKU_INFO)){

			$arResult   = array();
			$rsOffers = \CIBlockElement::GetList(array(),array("IBLOCK_ID" => $SKU_INFO["IBLOCK_ID"], "PROPERTY_".$SKU_INFO["SKU_PROPERTY_ID"] => $PRODUCT_ID), false, false, array("ID", "IBLOCK_ID", "DETAIL_PAGE_URL", "DETAIL_PICTURE", "NAME", "CATALOG_QUANTITY"));
			while($ob = $rsOffers->GetNextElement()){
				$arFields = $ob->GetFields();
				$arProps = $ob->GetProperties();
				$dbPrice = \CPrice::GetList(
					array("QUANTITY_FROM" => "ASC", "QUANTITY_TO" => "ASC", "SORT" => "ASC"),
					array(
						"PRODUCT_ID" => $arFields["ID"],
						"CATALOG_GROUP_ID" => $OPTION_BASE_PRICE
					),
					false,
					false,
					array("ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY", "QUANTITY_FROM", "QUANTITY_TO")
				);

				while ($arPrice = $dbPrice->Fetch()){
					$arDiscounts = \CCatalogDiscount::GetDiscountByPrice(
						$arPrice["ID"],
						$USER->GetUserGroupArray(),
						"N",
						SITE_ID
					);
					$arFields["PRICE"] = \CCatalogProduct::CountPriceWithDiscount(
						$arPrice["PRICE"],
						$arPrice["CURRENCY"],
						$arDiscounts
					);

					$arFields["DISCONT_PRICE"] = $arFields["PRICE"] != $arPrice["PRICE"] ? CurrencyFormat(\CCurrencyRates::ConvertCurrency($arPrice["PRICE"], $arPrice["CURRENCY"], $OPTION_CURRENCY), $OPTION_CURRENCY) : 0;
					$arFields["PRICE"] = CurrencyFormat(\CCurrencyRates::ConvertCurrency($arFields["PRICE"], $arPrice["CURRENCY"], $OPTION_CURRENCY), $OPTION_CURRENCY);

				}

				$picture = \CFile::ResizeImageGet($arFields['DETAIL_PICTURE'], array('width' => 220, 'height' => 200), BX_RESIZE_IMAGE_PROPORTIONAL, true);
				$arFields["DETAIL_PICTURE"] = !empty($picture["src"]) ? $picture["src"] : SITE_TEMPLATE_PATH."/images/empty.svg";
				$arFields["ADDCART"] = $OPTION_ADD_CART === "Y" ? true : $arFields["CATALOG_QUANTITY"] > 0;
				$arResult[] = array_merge($arFields, array("PROPERTIES" => $arProps));

			}

			foreach ($arResult[0]["PROPERTIES"] as $i => $arProp) {
				$propVisible = false;
				if(empty($arProp["VALUE"])){
					if(empty($propDelete[$i])){
						foreach ($arResult as $x => $arElement) {
							if(!empty($arElement["PROPERTIES"][$i]["VALUE"])){
								$propVisible = true;
								break;
							}
						}

						if($propVisible === false){
							$propDelete[$i] = true;
						}
					}
				}
			}

			foreach ($arResult as $i => $arElement) {
				foreach ($propDelete as $x => $val) {
					unset($arResult[$i]["PROPERTIES"][$x]);
				}
			}

			if(!empty($arResult)){
				echo Json::encode($arResult);
			}

		}

	}
}
elseif ($actionName == "addWishlist") {
    if (!check_bitrix_sessid()) {
        $response = new JsonResponse(["errors" => ["Неверный токен сессии"]]);
        $response->setStatus(403)->send();
        $application->terminate();
    }

    $id = (int)($request->getPost("id") ?? 0);
    if ($id > 0) {
        $wishlist = $session->get('WISHLIST_LIST') ?? [];

        if (!isset($wishlist['ITEMS'])) {
            $wishlist['ITEMS'] = [];
        }

        $wishlist['ITEMS'][$id] = $id;
        $session->set('WISHLIST_LIST', $wishlist);

        $response = new JsonResponse($id);
        $response->send();
        $application->terminate();
    } else {
        $response = new JsonResponse(0);
        $response->send();
        $application->terminate();
    }
}
elseif ($actionName == "removeWishlist") {
    if (!check_bitrix_sessid()) {
        $response = new JsonResponse(["errors" => ["Неверный токен сессии"]]);
        $response->setStatus(403)->send();
        $application->terminate();
    }

    $id = (int)($request->getPost("id") ?? 0);
    if ($id > 0) {
        $wishlist = $session->get('WISHLIST_LIST') ?? [];

        if (isset($wishlist['ITEMS'][$id])) {
            unset($wishlist['ITEMS'][$id]);

            $session->set('WISHLIST_LIST', $wishlist);

            $response = new JsonResponse(true);
            $response->send();
            $application->terminate();
        }
    }
    $response = new JsonResponse(false);
    $response->send();
    $application->terminate();
}
elseif ($actionName == "addCompare") {
    if (!check_bitrix_sessid()) {
        $response = new JsonResponse(["errors" => ["Неверный токен сессии"]]);
        $response->setStatus(403)->send();
        $application->terminate();
    }

    $id = (int)($request->getPost("id") ?? 0);
    if ($id > 0) {
        $compare = $session->get('COMPARE_LIST') ?? [];

        if (!isset($compare['ITEMS'])) {
            $compare['ITEMS'] = [];
        }

        $compare['ITEMS'][$id] = $id;

        $session->set('COMPARE_LIST', $compare);

        $response = new JsonResponse($id);
        $response->send();
        $application->terminate();
    } else {
        $response = new JsonResponse(0);
        $response->send();
        $application->terminate();
    }
}
elseif ($actionName == "compDEL") {
    if (!check_bitrix_sessid()) {
        $response = new JsonResponse(["errors" => ["Неверный токен сессии"]]);
        $response->setStatus(403)->send();
        $application->terminate();
    }

    $id = (int)($request->getPost("id") ?? 0);
    if ($id > 0) {
        $compare = $session->get('COMPARE_LIST') ?? [];

        if (isset($compare['ITEMS'][$id])) {
            unset($compare['ITEMS'][$id]);

            $session->set('COMPARE_LIST', $compare);

    		$response = new JsonResponse(['SUCCESS' => 'Y']);
            $response->send();
            $application->terminate();
        }
    }

    $response = new JsonResponse(false);
    $response->send();
    $application->terminate();
}
elseif ($actionName == "clearCompare") {
    if (!check_bitrix_sessid()) {
        $response = new JsonResponse(["errors" => ["Неверный токен сессии"]]);
        $response->setStatus(403)->send();
        $application->terminate();
    }

    $session->remove('COMPARE_LIST');

    $response = new JsonResponse(['SUCCESS' => 'Y']);
    $response->send();
    $application->terminate();
}elseif($actionName == "flushCart"){
	?>
	<ul>
		<li class="dl">
		<?$APPLICATION->IncludeComponent(
			"bitrix:sale.basket.basket.line",
			addslashes($_GET["topCartTemplate"]),
			array(
				"HIDE_ON_BASKET_PAGES" => "N",
				"PATH_TO_BASKET" => SITE_DIR."personal/cart/",
				"PATH_TO_ORDER" => SITE_DIR."personal/order/make/",
				"PATH_TO_PERSONAL" => SITE_DIR."personal/",
				"PATH_TO_PROFILE" => SITE_DIR."personal/",
				"PATH_TO_REGISTER" => SITE_DIR."login/",
				"POSITION_FIXED" => "N",
				"SHOW_AUTHOR" => "Y",
				"SHOW_EMPTY_VALUES" => "Y",
				"SHOW_NUM_PRODUCTS" => "Y",
				"SHOW_PERSONAL_LINK" => "N",
				"SHOW_PRODUCTS" => "Y",
				"SHOW_TOTAL_PRICE" => "Y",
				"COMPONENT_TEMPLATE" => "topCart"
			),
			false
		);?>
		</li>
		<li class="dl">
			<?$APPLICATION->IncludeComponent(
				"bitrix:sale.basket.basket.line",
				"bottomCart",
				array(
					"HIDE_ON_BASKET_PAGES" => "N",
					"PATH_TO_BASKET" => SITE_DIR."personal/cart/",
					"PATH_TO_ORDER" => SITE_DIR."personal/order/make/",
					"PATH_TO_PERSONAL" => SITE_DIR."personal/",
					"PATH_TO_PROFILE" => SITE_DIR."personal/",
					"PATH_TO_REGISTER" => SITE_DIR."login/",
					"POSITION_FIXED" => "N",
					"SHOW_AUTHOR" => "N",
					"SHOW_EMPTY_VALUES" => "Y",
					"SHOW_NUM_PRODUCTS" => "Y",
					"SHOW_PERSONAL_LINK" => "N",
					"SHOW_PRODUCTS" => "Y",
					"SHOW_TOTAL_PRICE" => "Y",
					"COMPONENT_TEMPLATE" => "topCart"
				),
				false
			);?>
		</li>
		<li class="dl">
			<?$APPLICATION->IncludeComponent("dresscode:favorite.line", addslashes($_GET["wishListTemplate"]), Array(
				),
				false
			);?>
		</li>
		<li class="dl">
			<?$APPLICATION->IncludeComponent("dresscode:compare.line", addslashes($_GET["compareTemplate"]), Array(

				),
				false
			);?>
		</li>
	</ul><?
}elseif($actionName == "rating"){
	if (!check_bitrix_sessid()) {
		$response = new JsonResponse(["errors" => [Loc::getMessage("AI0_DW_DELUXE_API_CSRF_ERROR")]]);
		$response->setStatus(403)->send();
		$application->terminate();
	}

	if (!$USER->IsAuthorized()) {
		$response = new JsonResponse([
			"result" => false,
			"error" => "Для голосования необходимо авторизоваться",
			"heading" => "Ошибка"
		]);
		$response->setStatus(401)->send();
		$application->terminate();
	}

	$productId = (int)$request->get("id");
	if ($productId <= 0) {
		$response = new JsonResponse([
			"result" => false,
			"error" => "Элемент не найден",
			"heading" => "Ошибка"
		]);
		$response->setStatus(404)->send();
		$application->terminate();
	}

	$trig = $request->get("trig");
	$isGood = ($trig === "1" || $trig === "good");

	$res = \CIBlockElement::GetList(
		[],
		[
			"ID" => $productId,
			"ACTIVE_DATE" => "Y",
			"ACTIVE" => "Y"
		],
		false,
		false,
		["ID", "IBLOCK_ID", "PROPERTY_USER_ID", "PROPERTY_GOOD_REVIEW", "PROPERTY_BAD_REVIEW"]
	);

	$arFields = $res->Fetch();
	if (!$arFields) {
		$response = new JsonResponse([
			"result" => false,
			"error" => "Элемент не найден",
			"heading" => "Ошибка"
		]);
		$response->setStatus(404)->send();
		$application->terminate();
	}

	$userId = $USER->GetID();
	$votedUsers = [];
	$propUserValue = $arFields["PROPERTY_USER_ID_VALUE"];
	if (!empty($propUserValue)) {
		$votedUsers = is_array($propUserValue) ? $propUserValue : [$propUserValue];
	}

	if (in_array($userId, $votedUsers)) {
		$response = new JsonResponse([
			"result" => false,
			"error" => "Вы уже голосовали!",
			"heading" => "Ошибка"
		]);
		$response->setStatus(409)->send();
		$application->terminate();
	}

	$propertyCode = $isGood ? "GOOD_REVIEW" : "BAD_REVIEW";
	$currentValue = (int)($arFields["PROPERTY_{$propertyCode}_VALUE"] ?? 0);
	$newValue = $currentValue + 1;

	$votedUsers[] = $userId;

	\CIBlockElement::SetPropertyValuesEx(
		$productId,
		$arFields["IBLOCK_ID"],
		[
			$propertyCode => $newValue,
			"USER_ID" => $votedUsers
		]
	);

	$response = new JsonResponse(["result" => true]);
	$response->send();
	$application->terminate();
}elseif($actionName == "newReview"){
	if (!check_bitrix_sessid()) {
		$response = new JsonResponse(["errors" => [Loc::getMessage("AI0_DW_DELUXE_API_CSRF_ERROR")]]);
		$response->setStatus(403)->send();
		$application->terminate();
	}

	$settings = \DwSettings::getInstance()->getCurrentSettings();
	$useAnonymousReviews = $settings['TEMPLATE_USE_ANONYMOUS_PRODUCT_REVIEWS'] ?? 'N';
	$reviewsIblockId = $settings['TEMPLATE_PRODUCT_REVIEW_IBLOCK_ID'] ?? null;
	$reviewsAlreadyAdded = $session->get('REVIEWS_ADDED') ?? [];

	if($reviewsIblockId === null) {
		$response = new JsonResponse(["errors" => ["В настройках решения не задан инфоблок отзывов о товарах"]]);
		$response->setStatus(500)->send();
		$application->terminate();
	}

	if(!$USER->IsAuthorized() && $useAnonymousReviews !== 'Y'){
		$response = new JsonResponse(["errors" => ["Для отправки отзыва необходимо авторизоваться"]]);
		$response->setStatus(403)->send();
		$application->terminate();
	}

	$productId = (int)$request->get('PRODUCT_ID');

	if ($productId <= 0) {
		$response = new JsonResponse(["errors" => ["Неверный ID товара"]]);
		$response->setStatus(400)->send();
		$application->terminate();
	}

	$arUsers = [$USER->GetID()];
	$arFields = null;

	$elementIterator = \CIBlockElement::GetList(
		[],
		[
			"ID" => $productId,
			"ACTIVE_DATE" => "Y",
			"ACTIVE" => "Y"
		],
		false,
		false,
		[
			"ID",
			"NAME",
			"IBLOCK_ID",
			"PROPERTY_USER_ID",
			"PROPERTY_VOTE_SUM",
			"PROPERTY_VOTE_COUNT"
		]
	);
	while($elementResult = $elementIterator->GetNextElement()){
		$arFields = $elementResult->GetFields();

		if(!empty($arUsers[0]) && $arFields["PROPERTY_USER_ID_VALUE"] == $arUsers[0]){
			$response = new JsonResponse(["heading" => "Ошибка", "message" => "Вы уже оставляли отзыв к этому товару."]);
			$response->send();
			$application->terminate();
		}

		$arUsers[] = $arFields["PROPERTY_USER_ID_VALUE"];
	}

    if ($arFields === null) {
        $response = new JsonResponse(["heading" => "Ошибка", "message" => "Товар не найден."]);
        $response->setStatus(404)->send();
        $application->terminate();
    }

	if(!empty($reviewsAlreadyAdded[$productId])){
		$response = new JsonResponse(["heading" => "Ошибка", "message" => "Вы уже оставляли отзыв к этому товару."]);
		$response->send();
		$application->terminate();
	}

	if(empty($_POST["DIGNITY"])      ||
		empty($_POST["SHORTCOMINGS"]) ||
		empty($_POST["COMMENT"])      ||
		empty($_POST["NAME"])         ||
		empty($_POST["USED"])         ||
		empty($_POST["RATING"])       ||
		empty($_POST["PRODUCT_NAME"])
	){
		$response = new JsonResponse(["heading" => "Ошибка", "message" => "Заполните все поля!"]);
		$response->send();
		$application->terminate();
	}

	$newReviewElement = new \CIBlockElement;

	$newReviewElementData = [
		"MODIFIED_BY"    => $USER->GetID(),
		"IBLOCK_SECTION_ID" => false,
		"IBLOCK_ID"      => (int)$reviewsIblockId ,
		"PROPERTY_VALUES"=> [
			"DIGNITY" => \htmlspecialcharsbx($_POST["DIGNITY"]),
			"SHORTCOMINGS" => \htmlspecialcharsbx($_POST["SHORTCOMINGS"]),
			"NAME" => \htmlspecialcharsbx($_POST["NAME"]),
			"EXPERIENCE" => intval($_POST["USED"]),
			"RATING" => intval($_POST["RATING"])
		],
		"NAME"           => \htmlspecialcharsbx($_POST["PRODUCT_NAME"]),
		"ACTIVE"         => "N",
		"DETAIL_TEXT"    => \htmlspecialcharsbx($_POST["COMMENT"]),
		"CODE"           => $productId
	];

	$reviewElementId = $newReviewElement->Add($newReviewElementData);

	if((int)$reviewElementId > 0){

		$session->set('REVIEWS_ADDED', [...$reviewsAlreadyAdded, $productId => 'Y']);

		$VOTE_SUM   = $arFields["PROPERTY_VOTE_SUM_VALUE"] + intval($_POST["RATING"]);
		$VOTE_COUNT = $arFields["PROPERTY_VOTE_COUNT_VALUE"] + 1;
		$RATING = ($VOTE_SUM / $VOTE_COUNT);

		\CIBlockElement::SetPropertyValuesEx(
			$productId,
			$arFields["IBLOCK_ID"],
			[
				"VOTE_SUM" => $VOTE_SUM,
				"VOTE_COUNT" => $VOTE_COUNT,
				"RATING" => $RATING,
				"USER_ID" => $arUsers
			]
		);

		$eventTypeId = "SALE_DRESSCODE_NEW_PRODUCT_REVIEW";

		$eventType = \CEventType::GetList([
			"LID" => LANGUAGE_ID,
			"TYPE_ID" => $eventTypeId
		])->Fetch();

		if(!$eventType) {
			$eventType = new \CEventType;
			$eventType->Add([
				'LID' => LANGUAGE_ID,
				'EVENT_NAME' => $eventTypeId,
				'NAME' => Loc::getMessage("AI0_DW_DELUXE_API_NEW_PRODUCT_REVIEW_EVENT_NAME"),
				'DESCRIPTION' => "#SITE#\n#NAME#\n#PRODUCT#\n#MODERATION_URL#\n#COMMENT#\n#DIGNITY#\n#SHORTCOMINGS#\n#USED#\n#RATING#",
			]);
		}

		$usedValue = '';
		$usedId = (int)$_POST["USED"];

		if ($usedId > 0) {
			$enumResult = \CIBlockPropertyEnum::GetList([], ["ID" => $usedId, "IBLOCK_ID" => $reviewsIblockId]);
			$enumItem = $enumResult->Fetch();

			if ($enumItem) {
				$usedValue = $enumItem["VALUE"];
			}
		}

		$moderationUrl = "https://{$serverName}".
			"/bitrix/admin/iblock_element_edit.php?IBLOCK_ID={$reviewsIblockId}" .
			"&ID={$reviewElementId}" .
			"&lang=" . LANGUAGE_ID;

		$templateExists = \CEventMessage::GetList($orderBy = "site_id", $orderDirection = "desc", ["TYPE" => $eventTypeId])->Fetch();
		if (!$templateExists) {
			$emailTemplate = Loc::getMessage("AI0_DW_DELUXE_API_NEW_PRODUCT_REVIEW_EMAIL_TEMPLATE");

			if ($emailTemplate) {
				$emailMessage = new \CEventMessage;
				$emailMessage->Add([
					'ACTIVE' => 'Y',
					'EVENT_NAME' => $eventTypeId,
					'LID' => SITE_ID,
					'EMAIL_FROM' => Option::get('main', 'email_from'),
					'EMAIL_TO' => Option::get('sale', 'order_email'),
					'BCC' => Option::get('main', 'email_from'),
					'SUBJECT' => Loc::getMessage("AI0_DW_DELUXE_API_NEW_PRODUCT_REVIEW_EMAIL_SUBJECT"),
					'BODY_TYPE' => 'html',
					'MESSAGE' => $emailTemplate,
				]);
			}
		}

		$mailFields = [
			"SITE" => $serverName,
			"NAME" => htmlspecialcharsbx($_POST["NAME"]),
			"PRODUCT" => $arFields["NAME"],
			"MODERATION_URL" => $moderationUrl,
			"COMMENT" => htmlspecialcharsbx($_POST["COMMENT"]),
			"DIGNITY" => htmlspecialcharsbx($_POST["DIGNITY"]),
			"SHORTCOMINGS" => htmlspecialcharsbx($_POST["SHORTCOMINGS"]),
			"USED" => $usedValue,
			"RATING" => (int)$_POST["RATING"],
		];

		Event::send([
			"EVENT_NAME" => $eventTypeId,
			"LID" => SITE_ID,
			"C_FIELDS" => $mailFields,
			"DUPLICATE" => "Y",
		]);

		$response = new JsonResponse([
			"heading" => "Отзыв добавлен",
			"message" => "Ваш отзыв будет опубликован после модерации.",
			"reload" => true
		]);

		$response->send();
		$application->terminate();

	} else {
		$elementLastError = \strip_tags($newReviewElement->LAST_ERROR);

		$response = new JsonResponse(["heading" => "Ошибка", "message" => "При добавлении отзыва произошла ошибка. {$elementLastError}"]);
		$response->send();
		$application->terminate();
	}
}

function getLastOffer($arLastFilter, $arProps, $productID, $opCurrency, $enableMorePictures = false, $arPrices = array()){

	if(!empty($_GET["product_width"]) && !empty($_GET["product_height"])){
		$arProductImage = array("width" => $_GET["product_width"], "height" => $_GET["product_height"]);
	}else{
		$arProductImage = array("width" => 220, "height" => 200);
	}

	$rsLastOffer = \CIBlockElement::GetList(
		array(),
		$arLastFilter, false, false,
		array(
			"ID",
			"NAME",
			"IBLOCK_ID",
			"DETAIL_PICTURE",
			"DETAIL_PAGE_URL",
			"CATALOG_QUANTITY",
			"CATALOG_AVAILABLE",
			"CATALOG_SUBSCRIBE",
			"PREVIEW_TEXT"
		)
	);
	if(!$rsLastOffer->SelectedRowsCount()){
		array_pop($arLastFilter);
		array_pop($arProps);

		return getLastOffer($arLastFilter, $arProps, $productID, $opCurrency, $enableMorePictures, $arPrices);
	}else{
		if($obReturnOffer = $rsLastOffer->GetNextElement()){

			$productFilelds = $obReturnOffer->GetFields();
			if($enableMorePictures){
				$productProperties = $obReturnOffer->GetProperties();
			}

			$productFilelds["IMAGES"] = array();
			$rsProductSelect = array("ID", "IBLOCK_ID", "DETAIL_PICTURE", "PREVIEW_TEXT");

			if(!empty($productFilelds["DETAIL_PICTURE"])){

				$arImageResize = \CFile::ResizeImageGet($productFilelds["DETAIL_PICTURE"], $arProductImage, BX_RESIZE_IMAGE_PROPORTIONAL, false);
				$productFilelds["PICTURE"] = $arImageResize["src"];

				$productFilelds["IMAGES"][] = array(
					"SMALL_PICTURE" => \CFile::ResizeImageGet($productFilelds["DETAIL_PICTURE"], array("width" => 50, "height" => 50), BX_RESIZE_IMAGE_PROPORTIONAL, false),
					"LARGE_PICTURE" => \CFile::ResizeImageGet($productFilelds["DETAIL_PICTURE"], array("width" => 300, "height" => 300), BX_RESIZE_IMAGE_PROPORTIONAL, false),
					"SUPER_LARGE_PICTURE" => \CFile::ResizeImageGet($productFilelds["DETAIL_PICTURE"], array("width" => 900, "height" => 900), BX_RESIZE_IMAGE_PROPORTIONAL, false)
				);
			}

			if(!empty($productProperties["MORE_PHOTO"]["VALUE"])){
				foreach ($productProperties["MORE_PHOTO"]["VALUE"] as $irp => $nextPictureID) {
					$productFilelds["IMAGES"][] = array(
						"SMALL_PICTURE" => \CFile::ResizeImageGet($nextPictureID, array("width" => 50, "height" => 50), BX_RESIZE_IMAGE_PROPORTIONAL, false),
						"LARGE_PICTURE" => \CFile::ResizeImageGet($nextPictureID, array("width" => 300, "height" => 300), BX_RESIZE_IMAGE_PROPORTIONAL, false),
						"SUPER_LARGE_PICTURE" => \CFile::ResizeImageGet($nextPictureID, array("width" => 900, "height" => 900), BX_RESIZE_IMAGE_PROPORTIONAL, false)
					);
				}
			}

			if(empty($productFilelds["DETAIL_PICTURE"]) || empty($productProperties["MORE_PHOTO"]["VALUE"])){
				if($rsProduct = \CIBlockElement::GetList(array(), array("ID" => $productID), false, false, $rsProductSelect)->GetNextElement()){

					$rsProductFields = $rsProduct->GetFields();
					if($enableMorePictures){
						$rsProductProperties = $rsProduct->GetProperties(array("sort" => "asc", "name" => "asc"), array("EMPTY" => "N"));
					}

					if(!empty($rsProductFields["DETAIL_PICTURE"]) || !empty($rsProductProperties["MORE_PHOTO"]["VALUE"])){
						if(!empty($rsProductFields["DETAIL_PICTURE"]) && empty($productFilelds["DETAIL_PICTURE"])){

							$arImageResize = \CFile::ResizeImageGet($rsProductFields["DETAIL_PICTURE"], $arProductImage, BX_RESIZE_IMAGE_PROPORTIONAL, false);
							$productFilelds["PICTURE"] = $arImageResize["src"];

							array_unshift($productFilelds["IMAGES"], array(
								"SMALL_PICTURE" => \CFile::ResizeImageGet($rsProductFields["DETAIL_PICTURE"], array("width" => 50, "height" => 50), BX_RESIZE_IMAGE_PROPORTIONAL, false),
								"LARGE_PICTURE" => \CFile::ResizeImageGet($rsProductFields["DETAIL_PICTURE"], array("width" => 300, "height" => 300), BX_RESIZE_IMAGE_PROPORTIONAL, false),
								"SUPER_LARGE_PICTURE" => \CFile::ResizeImageGet($rsProductFields["DETAIL_PICTURE"], array("width" => 900, "height" => 900), BX_RESIZE_IMAGE_PROPORTIONAL, false)
							));

						}
						if(!empty($rsProductProperties["MORE_PHOTO"]["VALUE"]) && empty($productProperties["MORE_PHOTO"]["VALUE"])){
							foreach ($rsProductProperties["MORE_PHOTO"]["VALUE"] as $irp => $nextPictureID) {
								if(!empty($nextPictureID)){
									$productFilelds["IMAGES"][] = array(
										"SMALL_PICTURE" => \CFile::ResizeImageGet($nextPictureID, array("width" => 50, "height" => 50), BX_RESIZE_IMAGE_PROPORTIONAL, false),
										"LARGE_PICTURE" => \CFile::ResizeImageGet($nextPictureID, array("width" => 300, "height" => 300), BX_RESIZE_IMAGE_PROPORTIONAL, false),
										"SUPER_LARGE_PICTURE" => \CFile::ResizeImageGet($nextPictureID, array("width" => 900, "height" => 900), BX_RESIZE_IMAGE_PROPORTIONAL, false)
									);
								}
							}
						}
					}else{
						if(empty($productFilelds["IMAGES"])){
							$productFilelds["IMAGES"][0]["SMALL_PICTURE"] = array("SRC" => SITE_TEMPLATE_PATH."/images/empty.svg");
							$productFilelds["IMAGES"][0]["LARGE_PICTURE"] = array("SRC" => SITE_TEMPLATE_PATH."/images/empty.svg");
							$productFilelds["IMAGES"][0]["SUPER_LARGE_PICTURE"] = array("SRC" => SITE_TEMPLATE_PATH."/images/empty.svg");
						}
					}
				}
			}

			if(empty($productFilelds["PICTURE"])){
				$productFilelds["PICTURE"] = SITE_TEMPLATE_PATH."/images/empty.svg";
			}

			//get price info
			$productFilelds["EXTRA_SETTINGS"] = array();
			$productFilelds["EXTRA_SETTINGS"]["PRODUCT_PRICE_ALLOW"] = array();
			$productFilelds["EXTRA_SETTINGS"]["PRODUCT_PRICE_ALLOW_FILTER"] = array();

			if(!empty($arPrices["PARAMS_PRICE_CODE"])){

				//get available prices code & id
				$arPricesInfo = \DwPrices::getPriceInfo($arPrices["PARAMS_PRICE_CODE"], $productFilelds["IBLOCK_ID"]);
				if(!empty($arPricesInfo)){
					$productFilelds["EXTRA_SETTINGS"]["PRODUCT_PRICE_ALLOW"] = $arPricesInfo["ALLOW"];
					$productFilelds["EXTRA_SETTINGS"]["PRODUCT_PRICE_ALLOW_FILTER"] = null;
				}

			}

			$productFilelds["PRICE"] = \DwPrices::getPricesByProductId($productFilelds["ID"], $productFilelds["EXTRA_SETTINGS"]["PRODUCT_PRICE_ALLOW"], $productFilelds["EXTRA_SETTINGS"]["PRODUCT_PRICE_ALLOW_FILTER"], $arPrices["PARAMS_PRICE_CODE"], $productFilelds["IBLOCK_ID"], $opCurrency);
			$productFilelds["PRICE"]["DISCOUNT_PRICE"] = \CCurrencyLang::CurrencyFormat($productFilelds["PRICE"]["DISCOUNT_PRICE"], $opCurrency, true);
			$productFilelds["PRICE"]["RESULT_PRICE"]["BASE_PRICE"] = \CCurrencyLang::CurrencyFormat($productFilelds["PRICE"]["RESULT_PRICE"]["BASE_PRICE"], $opCurrency, true);
			$productFilelds["PRICE"]["DISCOUNT_PRINT"] = \CCurrencyLang::CurrencyFormat($productFilelds["PRICE"]["RESULT_PRICE"]["DISCOUNT"], $opCurrency, true);
			$productFilelds["CAN_BUY"] = $productFilelds["CATALOG_AVAILABLE"];

			if(!empty($productFilelds["PRICE"]["EXTENDED_PRICES"])){
				$productFilelds["PRICE"]["EXTENDED_PRICES_JSON_DATA"] = Json::encode($productFilelds["PRICE"]["EXTENDED_PRICES"]);
			}

			if(!empty($productFilelds["PRICE"]["DISCOUNT"])){
				unset($productFilelds["PRICE"]["DISCOUNT"]);
			}

			if(!empty($productFilelds["PRICE"]["DISCOUNT_LIST"])){
				unset($productFilelds["PRICE"]["DISCOUNT_LIST"]);
			}

			$productFilelds["BASKET_STEP"] = 1;
			$rsMeasureRatio = \CCatalogMeasureRatio::getList(
				array(),
				array("PRODUCT_ID" => intval($productFilelds["ID"])),
				false,
				false,
				array()
			);

			if($arProductMeasureRatio = $rsMeasureRatio->Fetch()){
				if(!empty($arProductMeasureRatio["RATIO"])){
					$productFilelds["BASKET_STEP"] = $arProductMeasureRatio["RATIO"];
				}
			}

			if(empty($productFilelds["PREVIEW_TEXT"])){
				if(!empty($rsProductFields)){
					$productFilelds["PREVIEW_TEXT"] = $rsProductFields["PREVIEW_TEXT"];
				}else{
					if($rsProduct = \CIBlockElement::GetList(array(), array("ID" => $productID), false, false, $rsProductSelect)->GetNextElement()){
						$rsProductFields = $rsProduct->GetFields();
						$productFilelds["PREVIEW_TEXT"] = $rsProductFields["PREVIEW_TEXT"];
					}
				}
			}

			return array(
				"PRODUCT" => array_merge(
					$productFilelds, array(
						"PROPERTIES" => $obReturnOffer->GetProperties()
					)
				),
				"PROPERTIES" => $arProps
			);
		}
	}
}
