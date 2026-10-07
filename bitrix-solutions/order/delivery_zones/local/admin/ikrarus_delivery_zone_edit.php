<?php

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;

require_once $_SERVER['DOCUMENT_ROOT']
    . '/bitrix/modules/main/include/prolog_admin_before.php';

global $APPLICATION;

$APPLICATION->SetTitle(
    'Редактирование зоны доставки'
);

if (!Loader::includeModule('highloadblock')) {
    require $_SERVER['DOCUMENT_ROOT']
        . '/bitrix/modules/main/include/prolog_admin_after.php';

    CAdminMessage::ShowMessage(
        'Не подключен модуль highloadblock.'
    );

    require $_SERVER['DOCUMENT_ROOT']
        . '/bitrix/modules/main/include/epilog_admin.php';

    exit;
}

$hlBlock = HighloadBlockTable::getList([
    'filter' => [
        '=NAME' => 'DeliveryZones',
    ],
])->fetch();

if (!$hlBlock) {
    require $_SERVER['DOCUMENT_ROOT']
        . '/bitrix/modules/main/include/prolog_admin_after.php';

    CAdminMessage::ShowMessage(
        'Highload-блок «DeliveryZones» не найден.'
    );

    require $_SERVER['DOCUMENT_ROOT']
        . '/bitrix/modules/main/include/epilog_admin.php';

    exit;
}

$entity = HighloadBlockTable::compileEntity(
    $hlBlock
);

$zoneClass = $entity->getDataClass();

$zoneId = (int) (
    $_GET['ID']
    ?? $_POST['ID']
    ?? 0
);

$errors = [];
$zone = [];

if ($zoneId > 0) {
    $zone = $zoneClass::getByPrimary(
        $zoneId
    )->fetch();

    if (!$zone) {
        require $_SERVER['DOCUMENT_ROOT']
            . '/bitrix/modules/main/include/prolog_admin_after.php';

        CAdminMessage::ShowMessage(
            'Выбранная зона не найдена.'
        );

        require $_SERVER['DOCUMENT_ROOT']
            . '/bitrix/modules/main/include/epilog_admin.php';

        exit;
    }
}

/*
 * Обработка сохранения.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_bitrix_sessid()) {
        $errors[] =
            'Сессия истекла. Обновите страницу.';
    } elseif ($zoneId <= 0) {
        $errors[] =
            'Не указана зона для редактирования.';
    } else {
        $name = trim(
            (string) (
                $_POST['UF_NAME']
                ?? ''
            )
        );

        $zoneNumber = (int) (
            $_POST['UF_ZONE_NUMBER']
            ?? 0
        );

        $price = (float) (
            $_POST['UF_PRICE']
            ?? 0
        );

        $freeFrom = (float) (
            $_POST['UF_FREE_FROM']
            ?? 0
        );

        $priority = (int) (
            $_POST['UF_PRIORITY']
            ?? 100
        );

        $active = isset(
            $_POST['UF_ACTIVE']
        ) ? 1 : 0;

        $geoJson = trim(
            (string) (
                $_POST['UF_GEOJSON']
                ?? ''
            )
        );

        if ($name === '') {
            $errors[] =
                'Укажите название зоны.';
        }

        if ($zoneNumber <= 0) {
            $errors[] =
                'Укажите номер зоны.';
        }

        if ($price < 0) {
            $errors[] =
                'Стоимость не может быть отрицательной.';
        }

        if ($freeFrom < 0) {
            $errors[] =
                'Порог бесплатной доставки '
                . 'не может быть отрицательным.';
        }

        if ($priority < 0) {
            $errors[] =
                'Приоритет не может быть отрицательным.';
        }

        /*
         * Проверяем GeoJSON.
         */
        if ($geoJson === '') {
            $errors[] =
                'Полигон зоны не передан.';
        } else {
            try {
                $geometry = json_decode(
                    $geoJson,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                if (
                    !is_array($geometry)
                    || ($geometry['type'] ?? '')
                        !== 'Polygon'
                    || empty(
                        $geometry['coordinates'][0]
                    )
                ) {
                    $errors[] =
                        'Некорректная структура Polygon.';
                }
            } catch (\JsonException $exception) {
                $errors[] =
                    'Поле полигона содержит '
                    . 'некорректный JSON.';
            }
        }

        if (empty($errors)) {
            $data = [
                'UF_NAME' => $name,
                'UF_ZONE_NUMBER' => $zoneNumber,
                'UF_GEOJSON' => $geoJson,
                'UF_PRICE' => $price,
                'UF_FREE_FROM' => $freeFrom,
                'UF_PRIORITY' => $priority,
                'UF_ACTIVE' => $active,
            ];

            $result = $zoneClass::update(
                $zoneId,
                $data
            );

            if ($result->isSuccess()) {
                LocalRedirect(
                    '/bitrix/admin/'
                    . 'ikrarus_delivery_zone_edit.php'
                    . '?lang=' . LANGUAGE_ID
                    . '&ID=' . $zoneId
                );
            }

            $errors = $result->getErrorMessages();
        }
    }
}

/*
 * Если сохранение не выполнено, показываем:
 *
 * 1. данные POST;
 * 2. либо данные выбранной записи.
 */
$geoJson = (string) (
    $_POST['UF_GEOJSON']
    ?? $zone['UF_GEOJSON']
    ?? ''
);

$name = (string) (
    $_POST['UF_NAME']
    ?? $zone['UF_NAME']
    ?? ''
);

$zoneNumber = (int) (
    $_POST['UF_ZONE_NUMBER']
    ?? $zone['UF_ZONE_NUMBER']
    ?? 0
);

$price = (float) (
    $_POST['UF_PRICE']
    ?? $zone['UF_PRICE']
    ?? 0
);

$freeFrom = (float) (
    $_POST['UF_FREE_FROM']
    ?? $zone['UF_FREE_FROM']
    ?? 0
);

$priority = (int) (
    $_POST['UF_PRIORITY']
    ?? $zone['UF_PRIORITY']
    ?? 100
);

$active = isset($_POST['UF_ACTIVE'])
    ? 1
    : (int) (
        $zone['UF_ACTIVE']
        ?? 1
    );

/*
 * Загружаем все зоны для отображения
 * на одной карте.
 */
$allZones = [];

$zonesResult = $zoneClass::getList([
    'select' => [
        'ID',
        'UF_NAME',
        'UF_ZONE_NUMBER',
        'UF_GEOJSON',
        'UF_PRICE',
        'UF_ACTIVE',
        'UF_PRIORITY',
    ],
    'order' => [
        'UF_ZONE_NUMBER' => 'ASC',
        'ID' => 'ASC',
    ],
]);

while ($item = $zonesResult->fetch()) {
    $itemGeometry = json_decode(
        (string) $item['UF_GEOJSON'],
        true
    );

    if (
        !is_array($itemGeometry)
        || ($itemGeometry['type'] ?? '')
            !== 'Polygon'
        || empty(
            $itemGeometry['coordinates'][0]
        )
    ) {
        continue;
    }

    $allZones[] = [
        'id' => (int) $item['ID'],
        'number' => (int) $item['UF_ZONE_NUMBER'],
        'name' => (string) $item['UF_NAME'],
        'price' => (float) $item['UF_PRICE'],
        'active' => (int) $item['UF_ACTIVE'],
        'geoJson' => $itemGeometry,
    ];
}

/*
 * Подключаем административную часть Bitrix.
 */
require $_SERVER['DOCUMENT_ROOT']
    . '/bitrix/modules/main/include/prolog_admin_after.php';
?>

<?php foreach ($errors as $error): ?>
    <?php
    CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => $error,
    ]);
    ?>
<?php endforeach; ?>

<?php if ($zoneId <= 0): ?>
    <?php
    CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => 'Не выбрана зона для редактирования.',
    ]);
    ?>
<?php endif; ?>

<style>
    .ikrarus-zone-layout {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 20px;
        align-items: start;
    }

    .ikrarus-zone-sidebar {
        background: #ffffff;
        border: 1px solid #d9e0e7;
        padding: 16px;
    }

    .ikrarus-zone-sidebar h2 {
        margin-top: 0;
        font-size: 18px;
    }

    .ikrarus-zone-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .ikrarus-zone-list a {
        display: block;
        padding: 10px 12px;
        border: 1px solid #d9e0e7;
        background: #f8fafc;
        color: #1d4f91;
        text-decoration: none;
    }

    .ikrarus-zone-list a:hover {
        background: #eef5ff;
    }

    .ikrarus-zone-list a.is-selected {
        border-color: #2067b0;
        background: #e8f1fb;
        font-weight: 600;
    }

    .ikrarus-zone-editor {
        min-width: 0;
    }

    #ikrarus-zones-map {
        width: 100%;
        height: 650px;
        background: #eef1f4;
        border: 1px solid #d9e0e7;
    }

    .ikrarus-zone-form {
        margin-top: 16px;
        padding: 16px;
        background: #ffffff;
        border: 1px solid #d9e0e7;
    }

    .ikrarus-zone-form-row {
        margin-bottom: 14px;
    }

    .ikrarus-zone-form-row label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .ikrarus-zone-form-row input[type="text"],
    .ikrarus-zone-form-row input[type="number"] {
        width: 100%;
        max-width: 520px;
        box-sizing: border-box;
    }

    .ikrarus-zone-help {
        margin: 12px 0;
        padding: 10px 12px;
        background: #fff8dc;
        border: 1px solid #e5d28a;
    }

    .ikrarus-zone-legend {
        margin-top: 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
    }

    .ikrarus-zone-legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ikrarus-zone-color {
        width: 16px;
        height: 16px;
        border: 1px solid #555;
        display: inline-block;
    }

    @media (max-width: 900px) {
        .ikrarus-zone-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="ikrarus-zone-layout">

    <aside class="ikrarus-zone-sidebar">
        <h2>Зоны доставки</h2>

        <div class="ikrarus-zone-list">
            <?php foreach ($allZones as $zoneItem): ?>
                <?php
                $isSelected = (
                    (int) $zoneItem['id']
                    === $zoneId
                );

                $zoneUrl =
                    '/bitrix/admin/'
                    . 'ikrarus_delivery_zone_edit.php'
                    . '?lang=' . LANGUAGE_ID
                    . '&ID='
                    . (int) $zoneItem['id'];
                ?>

                <a
                    href="<?= htmlspecialcharsbx(
                        $zoneUrl
                    ) ?>"
                    class="<?= $isSelected
                        ? 'is-selected'
                        : '' ?>"
                >
                    Зона
                    <?= (int) $zoneItem['number'] ?>

                    —
                    <?= htmlspecialcharsbx(
                        $zoneItem['name']
                    ) ?>

                    <br>

                    <small>
                        <?= (int) $zoneItem['price'] ?>
                        ₽
                    </small>
                </a>
            <?php endforeach; ?>
        </div>

        <p>
            <a
                class="adm-btn"
                href="<?= htmlspecialcharsbx(
                    '/bitrix/admin/'
                    . 'ikrarus_delivery_zone_edit.php'
                    . '?lang=' . LANGUAGE_ID
                ) ?>"
            >
                Создать новую зону
            </a>
        </p>
    </aside>

    <main class="ikrarus-zone-editor">

        <div id="ikrarus-zones-map"></div>

        <div class="ikrarus-zone-legend">
            <div
                class="ikrarus-zone-legend-item"
            >
                <span
                    class="ikrarus-zone-color"
                    style="background:#ed4543"
                ></span>

                Выбранная зона
            </div>

            <div
                class="ikrarus-zone-legend-item"
            >
                <span
                    class="ikrarus-zone-color"
                    style="background:#777777"
                ></span>

                Остальные зоны
            </div>
        </div>

        <?php if ($zoneId > 0): ?>
            <form
                method="post"
                class="ikrarus-zone-form"
                id="ikrarus-zone-form"
            >
                <?= bitrix_sessid_post() ?>

                <input
                    type="hidden"
                    name="ID"
                    value="<?= $zoneId ?>"
                >

                <input
                    type="hidden"
                    name="UF_GEOJSON"
                    id="ikrarus-geojson"
                    value="<?= htmlspecialcharsbx(
                        $geoJson
                    ) ?>"
                >

                <div class="ikrarus-zone-help">
                    На карте цветом выделена выбранная
                    зона. Только её границы можно
                    редактировать. Остальные зоны показаны
                    для контроля пересечений.
                </div>

                <div
                    class="ikrarus-zone-form-row"
                >
                    <label for="UF_NAME">
                        Название зоны
                    </label>

                    <input
                        type="text"
                        id="UF_NAME"
                        name="UF_NAME"
                        value="<?= htmlspecialcharsbx(
                            $name
                        ) ?>"
                    >
                </div>

                <div
                    class="ikrarus-zone-form-row"
                >
                    <label for="UF_ZONE_NUMBER">
                        Номер зоны
                    </label>

                    <input
                        type="number"
                        id="UF_ZONE_NUMBER"
                        name="UF_ZONE_NUMBER"
                        min="1"
                        value="<?= $zoneNumber ?>"
                    >
                </div>

                <div
                    class="ikrarus-zone-form-row"
                >
                    <label for="UF_PRICE">
                        Стоимость доставки
                    </label>

                    <input
                        type="number"
                        id="UF_PRICE"
                        name="UF_PRICE"
                        min="0"
                        step="0.01"
                        value="<?= $price ?>"
                    >
                </div>

                <div
                    class="ikrarus-zone-form-row"
                >
                    <label for="UF_FREE_FROM">
                        Бесплатная доставка от суммы
                    </label>

                    <input
                        type="number"
                        id="UF_FREE_FROM"
                        name="UF_FREE_FROM"
                        min="0"
                        step="0.01"
                        value="<?= $freeFrom ?>"
                    >
                </div>

                <div
                    class="ikrarus-zone-form-row"
                >
                    <label for="UF_PRIORITY">
                        Приоритет
                    </label>

                    <input
                        type="number"
                        id="UF_PRIORITY"
                        name="UF_PRIORITY"
                        min="0"
                        value="<?= $priority ?>"
                    >
                </div>

                <div
                    class="ikrarus-zone-form-row"
                >
                    <label>
                        <input
                            type="checkbox"
                            name="UF_ACTIVE"
                            value="1"
                            <?= $active
                                ? 'checked'
                                : '' ?>
                        >

                        Зона активна
                    </label>
                </div>

                <input
                    type="submit"
                    name="save"
                    value="Сохранить зону"
                    class="adm-btn-save"
                >
            </form>
        <?php endif; ?>

    </main>
</div>

<?php

$apiKey = \Bitrix\Main\Config\Option::get(
    'angerro.yadelivery',
    'api_key',
    ''
);

if ($apiKey === '') {
    CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' =>
            'Не найден API-ключ Яндекс.Карт. '
            . 'Проверьте настройку '
            . 'angerro.yadelivery → api_key.',
    ]);
} else {
    ?>
    <script
        src="https://api-maps.yandex.ru/2.1/?apikey=<?= htmlspecialcharsbx(
            $apiKey
        ) ?>&lang=ru_RU"
        type="text/javascript"
    ></script>
    <?php
}
?>

<script>
    const ikrarusZones =
        <?= \CUtil::PhpToJSObject(
            $allZones
        ) ?>;

    const ikrarusSelectedZoneId =
        <?= (int) $zoneId ?>;

    const ikrarusInitialGeoJson =
        <?= \CUtil::PhpToJSObject(
            json_decode(
                $geoJson,
                true
            ) ?: []
        ) ?>;

    let ikrarusEditablePolygon = null;

    function ikrarusToYandexCoordinates(
        geoJsonCoordinates
    ) {
        return geoJsonCoordinates.map(
            function (ring) {
                return ring.map(
                    function (point) {
                        return [
                            point[1],
                            point[0],
                        ];
                    }
                );
            }
        );
    }

    function ikrarusToGeoJsonCoordinates(
        yandexCoordinates
    ) {
        return yandexCoordinates.map(
            function (ring) {
                return ring.map(
                    function (point) {
                        return [
                            Number(point[1]),
                            Number(point[0]),
                        ];
                    }
                );
            }
        );
    }

    function ikrarusZoneColor(zone) {
        const colors = [
            '#ed4543',
            '#4986cc',
            '#7ac74f',
            '#ffb300',
            '#9c6ade',
            '#00a99d',
        ];

        const index =
            Math.max(
                0,
                Number(zone.number) - 1
            ) % colors.length;

        return colors[index];
    }

    function ikrarusMakePolygon(
        zone,
        isEditable
    ) {
        const geoJson =
            zone.geoJson || {};

        const geoJsonCoordinates =
            geoJson.coordinates || [];

        const yandexCoordinates =
            ikrarusToYandexCoordinates(
                geoJsonCoordinates
            );

        const color =
            ikrarusZoneColor(zone);

        const polygon =
            new ymaps.Polygon(
                yandexCoordinates,
                {
                    hintContent:
                        'Зона '
                        + zone.number
                        + ' — '
                        + zone.name,
                },
                {
                    fillColor: color,
                    fillOpacity: isEditable
                        ? 0.28
                        : 0.08,
                    strokeColor: color,
                    strokeOpacity: isEditable
                        ? 1
                        : 0.75,
                    strokeWidth: isEditable
                        ? 4
                        : 2,
                    draggable: false,
                    cursor: isEditable
                        ? 'pointer'
                        : 'default',
                }
            );

        polygon.properties.set(
            'zoneId',
            zone.id
        );

        polygon.properties.set(
            'zoneNumber',
            zone.number
        );

        polygon.properties.set(
            'isEditable',
            isEditable
        );

        return polygon;
    }

    function ikrarusSetGeoJsonFromPolygon() {
        if (!ikrarusEditablePolygon) {
            return;
        }

        const coordinates =
            ikrarusEditablePolygon.geometry
                .getCoordinates();

        const geoJson = {
            type: 'Polygon',
            coordinates:
                ikrarusToGeoJsonCoordinates(
                    coordinates
                ),
        };

        const field =
            document.getElementById(
                'ikrarus-geojson'
            );

        if (field) {
            field.value =
                JSON.stringify(geoJson);
        }
    }

    function ikrarusStartEditing(
        polygon
    ) {
        ikrarusEditablePolygon =
            polygon;

        polygon.editor.startEditing();

        polygon.geometry.events.add(
            'change',
            ikrarusSetGeoJsonFromPolygon
        );

        ikrarusSetGeoJsonFromPolygon();
    }

    ymaps.ready(function () {
        const map = new ymaps.Map(
            'ikrarus-zones-map',
            {
                center: [
                    55.55,
                    37.20,
                ],
                zoom: 8,
                controls: [
                    'zoomControl',
                    'typeSelector',
                    'fullscreenControl',
                ],
            }
        );

        ikrarusZones.forEach(
            function (zone) {
                const isEditable =
                    Number(zone.id)
                    === Number(
                        ikrarusSelectedZoneId
                    );

                const polygon =
                    ikrarusMakePolygon(
                        zone,
                        isEditable
                    );

                map.geoObjects.add(
                    polygon
                );

                if (isEditable) {
                    ikrarusStartEditing(
                        polygon
                    );
                }
            }
        );

        if (
            map.geoObjects.getLength() > 0
        ) {
            map.setBounds(
                map.geoObjects.getBounds(),
                {
                    checkZoomRange: true,
                    zoomMargin: 40,
                }
            );
        }

        const form =
            document.getElementById(
                'ikrarus-zone-form'
            );

        if (form) {
            form.addEventListener(
                'submit',
                function () {
                    ikrarusSetGeoJsonFromPolygon();
                }
            );
        }
    });
</script>

<?php

require $_SERVER['DOCUMENT_ROOT']
    . '/bitrix/modules/main/include/epilog_admin.php';