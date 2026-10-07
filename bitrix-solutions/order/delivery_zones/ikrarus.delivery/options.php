<?php

use Bitrix\Main\Localization\Loc;

defined('B_PROLOG_INCLUDED') || die();

$moduleId = 'ikrarus.delivery';

if (
    !\Bitrix\Main\Loader::includeModule(
        'main'
    )
) {
    return;
}

if (
    !\Bitrix\Main\Context::getCurrent()
        ->getRequest()
        ->isAdminSection()
) {
    return;
}

$request = \Bitrix\Main\Context::getCurrent()
    ->getRequest();

$tabControl = new CAdminTabControl(
    'tabControl',
    [
        [
            'DIV' => 'edit1',
            'TAB' => 'Настройки',
            'TITLE' => 'Настройки расчета доставки',
        ],
    ]
);

if (
    $request->isPost()
    && check_bitrix_sessid()
) {
    $yandexApiKey = trim(
        (string) $request->getPost(
            'YANDEX_API_KEY'
        )
    );

    $hlBlockId = (int) $request->getPost(
        'HL_BLOCK_ID'
    );

    $deliveryId = (int) $request->getPost(
        'DELIVERY_ID'
    );

    \Bitrix\Main\Config\Option::set(
        $moduleId,
        'YANDEX_API_KEY',
        $yandexApiKey
    );

    \Bitrix\Main\Config\Option::set(
        $moduleId,
        'HL_BLOCK_ID',
        (string) $hlBlockId
    );

    \Bitrix\Main\Config\Option::set(
        $moduleId,
        'DELIVERY_ID',
        (string) $deliveryId
    );

    LocalRedirect(
        $APPLICATION->GetCurPage()
        . '?mid='
        . urlencode($moduleId)
        . '&lang='
        . LANGUAGE_ID
    );
}

$yandexApiKey = \Bitrix\Main\Config\Option::get(
    $moduleId,
    'YANDEX_API_KEY',
    ''
);

$hlBlockId = (int) \Bitrix\Main\Config\Option::get(
    $moduleId,
    'HL_BLOCK_ID',
    '4'
);

$deliveryId = (int) \Bitrix\Main\Config\Option::get(
    $moduleId,
    'DELIVERY_ID',
    '5'
);

$tabControl->Begin();

$tabControl->BeginNextTab();

?>
<tr>
    <td width="40%">
        <label for="YANDEX_API_KEY">
            API-ключ Яндекс Maps:
        </label>
    </td>
    <td width="60%">
        <input
            type="text"
            name="YANDEX_API_KEY"
            id="YANDEX_API_KEY"
            value="<?= htmlspecialcharsbx(
                $yandexApiKey
            ) ?>"
            size="60"
        >
    </td>
</tr>

<tr>
    <td>
        <label for="HL_BLOCK_ID">
            ID HL-блока зон:
        </label>
    </td>
    <td>
        <input
            type="number"
            name="HL_BLOCK_ID"
            id="HL_BLOCK_ID"
            value="<?= $hlBlockId ?>"
            min="1"
        >
    </td>
</tr>

<tr>
    <td>
        <label for="DELIVERY_ID">
            ID службы доставки:
        </label>
    </td>
    <td>
        <input
            type="number"
            name="DELIVERY_ID"
            id="DELIVERY_ID"
            value="<?= $deliveryId ?>"
            min="1"
        >
    </td>
</tr>

<?php

$tabControl->Buttons();

?>
<input
    type="submit"
    name="Update"
    value="Сохранить"
    class="adm-btn-save"
>

<?= bitrix_sessid_post() ?>

<?php

$tabControl->End();