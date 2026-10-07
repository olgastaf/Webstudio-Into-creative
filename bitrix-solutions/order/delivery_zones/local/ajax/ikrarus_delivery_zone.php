<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Highloadblock\HighloadBlockTable;

define('PUBLIC_AJAX_MODE', true);

require_once(
    $_SERVER['DOCUMENT_ROOT']
    . '/bitrix/modules/main/include/prolog_before.php'
);

header('Content-Type: application/json; charset=utf-8');

function jsonResponse(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function pointInPolygon(
    float $longitude,
    float $latitude,
    array $ring
): bool {
    $inside = false;
    $count = count($ring);

    if ($count < 3) {
        return false;
    }

    $j = $count - 1;

    for ($i = 0; $i < $count; $i++) {
        $pointA = $ring[$i];
        $pointB = $ring[$j];

        if (
            !is_array($pointA) ||
            !is_array($pointB) ||
            count($pointA) < 2 ||
            count($pointB) < 2
        ) {
            $j = $i;
            continue;
        }

        /*
         * GeoJSON:
         * [0] — долгота
         * [1] — широта
         */

        $x1 = (float)$pointA[0];
        $y1 = (float)$pointA[1];

        $x2 = (float)$pointB[0];
        $y2 = (float)$pointB[1];

        $intersects = (
            (($y1 > $latitude) !== ($y2 > $latitude))
            &&
            (
                $longitude
                <
                ($x2 - $x1)
                * ($latitude - $y1)
                / (($y2 - $y1) ?: 0.0000000001)
                + $x1
            )
        );

        if ($intersects) {
            $inside = !$inside;
        }

        $j = $i;
    }

    return $inside;
}

function geocodeAddress(
    string $address,
    string $apiKey
): array {
    $url = 'https://geocode-maps.yandex.ru/1.x/?'
        . http_build_query([
            'apikey' => $apiKey,
            'geocode' => $address,
            'format' => 'json',
            'results' => 1,
        ]);

    $httpClient = new HttpClient();

    $httpClient->setTimeout(10);
    $httpClient->setStreamTimeout(10);

    $response = $httpClient->get($url);

    if ($response === false || trim($response) === '') {
        throw new RuntimeException(
            'Геокодер не вернул ответ'
        );
    }

    $data = json_decode(
        $response,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $members = $data[
        'response'
    ][
        'GeoObjectCollection'
    ][
        'featureMember'
    ] ?? [];

    if (!$members) {
        throw new RuntimeException(
            'Адрес не найден'
        );
    }

    $position = $members[0][
        'GeoObject'
    ][
        'Point'
    ][
        'pos'
    ] ?? '';

    if ($position === '') {
        throw new RuntimeException(
            'Геокодер не вернул координаты'
        );
    }

    $coordinates = preg_split(
        '/\s+/',
        trim($position)
    );

    if (
        count($coordinates) < 2 ||
        !is_numeric($coordinates[0]) ||
        !is_numeric($coordinates[1])
    ) {
        throw new RuntimeException(
            'Некорректные координаты геокодера'
        );
    }

    return [
        'longitude' => (float)$coordinates[0],
        'latitude' => (float)$coordinates[1],
    ];
}

if (!Loader::includeModule('highloadblock')) {
    jsonResponse([
        'success' => false,
        'error' => 'Не подключен модуль highloadblock',
    ], 500);
}

$rawInput = file_get_contents('php://input');

$input = json_decode(
    $rawInput,
    true
);

if (!is_array($input)) {
    $input = $_POST;
}

$address = trim(
    (string)($input['address'] ?? '')
);

$longitude = isset($input['longitude'])
    ? (float)$input['longitude']
    : null;

$latitude = isset($input['latitude'])
    ? (float)$input['latitude']
    : null;

$orderPrice = isset($input['orderPrice'])
    ? (float)$input['orderPrice']
    : 0;

$geocoderApiKey = 'b2e19de9-c203-45cc-be6d-84c9f7bf500b';

try {
    if ($address !== '') {
        $coordinates = geocodeAddress(
            $address,
            $geocoderApiKey
        );

        $longitude = $coordinates['longitude'];
        $latitude = $coordinates['latitude'];
    }

    if (
        $longitude === null ||
        $latitude === null
    ) {
        jsonResponse([
            'success' => false,
            'error' => 'Передайте address или longitude и latitude',
        ], 400);
    }

    if (
        $longitude < -180 ||
        $longitude > 180 ||
        $latitude < -90 ||
        $latitude > 90
    ) {
        jsonResponse([
            'success' => false,
            'error' => 'Некорректные координаты',
        ], 400);
    }

    $hlBlock = HighloadBlockTable::getList([
        'filter' => [
            '=NAME' => 'DeliveryZones',
        ],
    ])->fetch();

    if (!$hlBlock) {
        jsonResponse([
            'success' => false,
            'error' => 'Highload-блок DeliveryZones не найден',
        ], 500);
    }

    $entity = HighloadBlockTable::compileEntity(
        $hlBlock
    );

    $zoneClass = $entity->getDataClass();

    $zones = $zoneClass::getList([
        'filter' => [
            '=UF_ACTIVE' => 1,
        ],
        'order' => [
            'UF_PRIORITY' => 'ASC',
            'UF_SORT' => 'ASC',
            'ID' => 'ASC',
        ],
        'select' => [
            'ID',
            'UF_NAME',
            'UF_ZONE_NUMBER',
            'UF_GEOJSON',
            'UF_PRICE',
            'UF_FREE_FROM',
            'UF_PRIORITY',
            'UF_SORT',
        ],
    ])->fetchAll();

    $matchedZones = [];

    foreach ($zones as $zone) {
        if (empty($zone['UF_GEOJSON'])) {
            continue;
        }

        try {
            $geometry = json_decode(
                $zone['UF_GEOJSON'],
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $exception) {
            continue;
        }

        if (
            !is_array($geometry) ||
            ($geometry['type'] ?? '') !== 'Polygon'
        ) {
            continue;
        }

        $rings = $geometry['coordinates'] ?? [];

        if (
            empty($rings) ||
            !is_array($rings[0])
        ) {
            continue;
        }

        if (pointInPolygon(
            $longitude,
            $latitude,
            $rings[0]
        )) {
            $matchedZones[] = $zone;
        }
    }

    if (!$matchedZones) {
        jsonResponse([
            'success' => true,
            'found' => false,
            'message' => 'Адрес находится вне зон доставки',
            'address' => $address,
            'coordinates' => [
                'longitude' => $longitude,
                'latitude' => $latitude,
            ],
        ]);
    }

    $selectedZone = $matchedZones[0];

    $deliveryPrice = (float)$selectedZone['UF_PRICE'];
    $freeFrom = (float)$selectedZone['UF_FREE_FROM'];

    if (
        $freeFrom > 0 &&
        $orderPrice >= $freeFrom
    ) {
        $deliveryPrice = 0;
    }

    jsonResponse([
        'success' => true,
        'found' => true,
        'address' => $address,
        'zone' => [
            'id' => (int)$selectedZone['ID'],
            'number' => (int)$selectedZone['UF_ZONE_NUMBER'],
            'name' => (string)$selectedZone['UF_NAME'],
            'priority' => (int)$selectedZone['UF_PRIORITY'],
        ],
        'delivery' => [
            'price' => $deliveryPrice,
            'currency' => 'RUB',
            'freeFrom' => $freeFrom,
        ],
        'coordinates' => [
            'longitude' => $longitude,
            'latitude' => $latitude,
        ],
    ]);
} catch (\JsonException $exception) {
    jsonResponse([
        'success' => false,
        'error' => 'Ошибка разбора ответа Геокодера',
        'details' => $exception->getMessage(),
    ], 502);
} catch (\Throwable $exception) {
    jsonResponse([
        'success' => false,
        'error' => $exception->getMessage(),
    ], 500);
}