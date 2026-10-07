<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;

$APPLICATION->SetTitle('Зоны доставки');

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm('Доступ запрещен');
}

if (!Loader::includeModule('highloadblock')) {
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

    CAdminMessage::ShowMessage(
        'Не подключен модуль highloadblock'
    );

    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
    exit;
}

$hlBlock = HighloadBlockTable::getList([
    'filter' => [
        '=NAME' => 'DeliveryZones',
    ],
])->fetch();

if (!$hlBlock) {
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

    CAdminMessage::ShowMessage(
        'Highload-блок «DeliveryZones» не найден'
    );

    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
    exit;
}

$entity = HighloadBlockTable::compileEntity($hlBlock);
$zoneClass = $entity->getDataClass();

$zones = $zoneClass::getList([
    'order' => [
        'UF_SORT' => 'ASC',
        'ID' => 'ASC',
    ],
    'select' => [
        'ID',
        'UF_NAME',
        'UF_ZONE_NUMBER',
        'UF_PRICE',
        'UF_ACTIVE',
        'UF_PRIORITY',
        'UF_SORT',
    ],
])->fetchAll();

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
?>

<div style="margin-bottom: 15px;">
    <a
        class="adm-btn adm-btn-save"
        href="/bitrix/admin/ikrarus_delivery_zone_edit.php?lang=<?= LANGUAGE_ID ?>"
    >
        Добавить зону
    </a>
</div>

<table class="adm-list-table">
    <thead>
        <tr class="adm-list-table-header">
            <th class="adm-list-table-cell">ID</th>
            <th class="adm-list-table-cell">Номер</th>
            <th class="adm-list-table-cell">Название</th>
            <th class="adm-list-table-cell">Стоимость</th>
            <th class="adm-list-table-cell">Приоритет</th>
            <th class="adm-list-table-cell">Активна</th>
            <th class="adm-list-table-cell">Действия</th>
        </tr>
    </thead>

    <tbody>
        <?php if (!$zones): ?>
            <tr class="adm-list-table-row">
                <td
                    class="adm-list-table-cell"
                    colspan="7"
                >
                    Зоны доставки еще не созданы.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($zones as $zone): ?>
                <tr class="adm-list-table-row">
                    <td class="adm-list-table-cell">
                        <?= (int)$zone['ID'] ?>
                    </td>

                    <td class="adm-list-table-cell">
                        <?= (int)$zone['UF_ZONE_NUMBER'] ?>
                    </td>

                    <td class="adm-list-table-cell">
                        <?= htmlspecialcharsbx(
                            (string)$zone['UF_NAME']
                        ) ?>
                    </td>

                    <td class="adm-list-table-cell">
                        <?= htmlspecialcharsbx(
                            (string)$zone['UF_PRICE']
                        ) ?> ₽
                    </td>

                    <td class="adm-list-table-cell">
                        <?= (int)$zone['UF_PRIORITY'] ?>
                    </td>

                    <td class="adm-list-table-cell">
                        <?= $zone['UF_ACTIVE'] ? 'Да' : 'Нет' ?>
                    </td>

                    <td class="adm-list-table-cell">
                        <a
                            href="/bitrix/admin/ikrarus_delivery_zone_edit.php?lang=<?= LANGUAGE_ID ?>&ID=<?= (int)$zone['ID'] ?>"
                        >
                            Редактировать
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');