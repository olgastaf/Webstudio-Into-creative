<?php
AddEventHandler(
    'main',
    'OnBeforeUserAdd',
    'infoCreativeValidateBusinessRegistration'
);

function infoCreativeValidateBusinessRegistration(&$fields)
{
    global $APPLICATION;

    // Применяем правила только к нашему подключению регистрации.
    if (
        !defined('INDIVIDUAL_BUSINESS_REGISTRATION')
        || INDIVIDUAL_BUSINESS_REGISTRATION !== true
        || (defined('ADMIN_SECTION') && ADMIN_SECTION === true)
    ) {
        return true;
    }
$request = \Bitrix\Main\Context::getCurrent()->getRequest();

if (!check_bitrix_sessid()) {
    $APPLICATION->ThrowException('Сессия истекла. Обновите страницу и повторите регистрацию.');
    return false;
}

// Не доверяем скрытому полю LOGIN.
$email = $fields['EMAIL'] ?? '';
$fields['LOGIN'] = is_string($email) ? trim($email) : '';


if ($request->getPost('registration_consent') !== 'Y') {
    $APPLICATION->ThrowException(
        'Для регистрации необходимо согласие на обработку персональных данных.'
    );
    return false;
}
    // На другом сайте ID группы нужно изменить.
    $businessGroupId = 8;

    // Получаем поле типа клиента.
    $userField = CUserTypeEntity::GetList(
        [],
        [
            'ENTITY_ID' => 'USER',
            'FIELD_NAME' => 'UF_CLIENT_TYPE',
        ]
    )->Fetch();

    if (!$userField) {
        $APPLICATION->ThrowException(
            'Не настроено поле типа клиента. Обратитесь к администратору.'
        );
        return false;
    }

    // Определяем ID вариантов по постоянным XML_ID.
    $typeIds = [];

    $enumResult = CUserFieldEnum::GetList(
        [],
        ['USER_FIELD_ID' => (int)$userField['ID']]
    );

    while ($enum = $enumResult->Fetch()) {
        if (in_array($enum['XML_ID'], ['individual', 'business'], true)) {
            $typeIds[$enum['XML_ID']] = (string)$enum['ID'];
        }
    }

    if (!isset($typeIds['individual'], $typeIds['business'])) {
        $APPLICATION->ThrowException(
            'Не настроены варианты типа клиента. Обратитесь к администратору.'
        );
        return false;
    }

    $selectedType = $fields['UF_CLIENT_TYPE'] ?? null;

    if (
        !is_scalar($selectedType)
        || !in_array((string)$selectedType, array_values($typeIds), true)
    ) {
        $APPLICATION->ThrowException('Выберите тип клиента.');
        return false;
    }

    $isBusiness = (string)$selectedType === $typeIds['business'];
    $errors = [];

    // Нормализуем реквизиты и не принимаем массивы вместо строк.
    foreach (
        ['WORK_COMPANY', 'UF_INN', 'UF_KPP', 'UF_BUSINESS_ADDRESS']
        as $fieldName
    ) {
        $value = $fields[$fieldName] ?? '';

        if (!is_scalar($value)) {
            $errors[] = 'Некорректный формат реквизитов.';
            $fields[$fieldName] = '';
        } else {
            $fields[$fieldName] = trim((string)$value);
        }
    }

    if ($isBusiness) {
        if ($fields['WORK_COMPANY'] === '') {
            $errors[] = 'Укажите название организации или ИП.';
        }

        $inn = $fields['UF_INN'];

        // Пока проверяем формат: 10 цифр для организации, 12 для ИП.
        if (!preg_match('/\A(?:[0-9]{10}|[0-9]{12})\z/', $inn)) {
            $errors[] = 'ИНН должен содержать 10 или 12 цифр.';
        } elseif (strlen($inn) === 10) {
            if (!preg_match('/\A[0-9]{9}\z/', $fields['UF_KPP'])) {
                $errors[] = 'Укажите КПП организации: 9 цифр.';
            }
        } else {
            // Для ИП КПП не используется.
            $fields['UF_KPP'] = '';
        }

        if ($fields['UF_BUSINESS_ADDRESS'] === '') {
            $errors[] = 'Укажите адрес организации или ИП.';
        }
    } else {
        // Не сохраняем реквизиты для частного лица.
        foreach (
            ['WORK_COMPANY', 'UF_INN', 'UF_KPP', 'UF_BUSINESS_ADDRESS']
            as $fieldName
        ) {
            $fields[$fieldName] = '';
        }
    }

    if ($errors) {
        $APPLICATION->ThrowException(
            implode("\n", array_unique($errors))
        );
        return false;
    }

    // Сохраняем стандартные группы, исключая бизнес-группу.
    $groups = [];

    foreach ((array)($fields['GROUP_ID'] ?? []) as $group) {
        $groupId = is_array($group)
            ? (int)($group['GROUP_ID'] ?? 0)
            : (int)$group;

        if ($groupId !== $businessGroupId) {
            $groups[] = $group;
        }
    }

    if ($isBusiness) {
        $groups[] = $businessGroupId;
    }

    $fields['GROUP_ID'] = $groups;

    return true;
}
