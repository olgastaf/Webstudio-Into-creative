<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// VALUES уже экранированы компонентом. Декодируем и экранируем один раз
// непосредственно при выводе в атрибуты HTML.
$values = $arResult['VALUES'] ?? [];
$value = static function ($key) use ($values) {
    $v = $values[$key] ?? '';
    return is_scalar($v) ? htmlspecialcharsback((string)$v) : '';
};
$properties = $arResult['USER_PROPERTIES']['DATA'] ?? [];
$propertyValue = static function ($key) use ($properties) {
    // При ошибке стандартный редактор Битрикса берёт значения из запроса.
    $v = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$key]))
        ? $_POST[$key]
        : ($properties[$key]['VALUE'] ?? '');
    if (is_array($v)) {
        $v = reset($v);
    }
    return is_scalar($v) ? (string)$v : '';
};
$types = [];
if (!empty($properties['UF_CLIENT_TYPE']['ID'])) {
    $enumResult = CUserFieldEnum::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        ['USER_FIELD_ID' => (int)$properties['UF_CLIENT_TYPE']['ID']]
    );
    while ($enum = $enumResult->Fetch()) {
        if (in_array($enum['XML_ID'], ['individual', 'business'], true)) {
            $types[$enum['XML_ID']] = $enum;
        }
    }
}
$selected = $propertyValue('UF_CLIENT_TYPE');
if (empty($arResult['bVarsFromForm'])) {
    $selected = (string)($types['individual']['ID'] ?? '');
}
$isBusiness = isset($types['business']) && $selected === (string)$types['business']['ID'];
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST'
    && (isset($_POST['register_submit_button']) || isset($_POST['code_submit_button']));
$created = (int)($values['USER_ID'] ?? 0) > 0;
$sms = !empty($arResult['SHOW_SMS_FIELD']);
$errors = $arResult['ERRORS'] ?? [];
$success = $created && !$sms;
$open = $submitted || !empty($errors) || $sms;
$authorized = $USER->IsAuthorized();

// У этих input подпись всегда связана с уникальным id.
$renderInput = static function ($key, $label, $name, $fieldValue, $type, $required, $autocomplete, $disabled = false, $business = false) {
    $id = 'reg-' . strtolower($key);
    ?>
    <div class="registration-field"<?= $business ? ' data-business-field' : '' ?><?= $disabled ? ' hidden' : '' ?>>
        <input class="registration-field__input" id="<?=htmlspecialcharsbx($id)?>"
            type="<?=htmlspecialcharsbx($type)?>" name="<?=htmlspecialcharsbx($name)?>"
            value="<?=htmlspecialcharsbx($fieldValue)?>" placeholder=" "
            autocomplete="<?=htmlspecialcharsbx($autocomplete)?>"
            <?=$required ? 'required' : ''?> <?=$disabled ? 'disabled' : ''?>
            <?=$business && $required ? 'data-business-required' : ''?>
            <?=in_array($key, ['UF_INN', 'UF_KPP'], true) ? 'inputmode="numeric"' : ''?>
            <?=$key === 'UF_INN' ? 'maxlength="12"' : ''?>
            <?=$key === 'UF_KPP' ? 'maxlength="9"' : ''?>>
        <label class="registration-field__label" for="<?=htmlspecialcharsbx($id)?>">
            <?=htmlspecialcharsbx($label)?><?=$required ? ' *' : ''?>
        </label>
    </div>
    <?php
};
?>
<div class="bx-auth-reg registration" data-registration-open="<?=$open ? 'Y' : 'N'?>">
    <h2 id="registration-title" class="registration__title">Регистрация</h2>

    <?php if ($errors): ?>
        <div class="registration-message registration-message--error" role="alert" tabindex="-1">
            <?php foreach ($errors as $key => $error):
                if (!is_scalar($error)) {
                    $error = 'Не удалось выполнить вход после регистрации. Попробуйте войти через форму авторизации.';
                }
                $caption = GetMessage('REGISTER_FIELD_' . $key) ?: (string)$key;
                $message = str_replace('#FIELD_NAME#', $caption, (string)$error);
                // Ошибки Битрикса могут содержать HTML-разрывы строк.
                $message = preg_replace('~<br\s*/?>~i', "\n", $message);
                ?>
                <div><?=nl2br(htmlspecialcharsbx(htmlspecialcharsback(strip_tags($message))))?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($sms):
        CJSCore::Init('phone_auth'); ?>
        <p class="registration__description">Введите код подтверждения из SMS.</p>
        <form method="post" action="<?=POST_FORM_ACTION_URI?>" name="regform" class="registration-form">
            <?=bitrix_sessid_post()?>
            <input type="hidden" name="SIGNED_DATA" value="<?=htmlspecialcharsbx($arResult['SIGNED_DATA'])?>">
            <?php $renderInput('SMS_CODE', 'Код из SMS', 'SMS_CODE', htmlspecialcharsback((string)($arResult['SMS_CODE'] ?? '')), 'text', true, 'one-time-code'); ?>
            <button class="registration-button" type="submit" name="code_submit_button" value="Y">Подтвердить телефон</button>
        </form>
        <div id="bx_register_error" class="registration-message registration-message--error" hidden><span class="errortext"></span></div>
        <div id="bx_register_resend"></div>
        <script>
        BX.ready(function () {
            new BX.PhoneAuth({
                containerId: 'bx_register_resend',
                errorContainerId: 'bx_register_error',
                interval: <?=(int)$arResult['PHONE_CODE_RESEND_INTERVAL']?>,
                data: <?=\Bitrix\Main\Web\Json::encode(['signedData' => $arResult['SIGNED_DATA']])?>,
                onError: function (response) {
                    var box = document.getElementById('bx_register_error');
                    box.querySelector('.errortext').textContent = response.errors.map(function (item) { return item.message; }).join('\n');
                    box.hidden = false;
                }
            });
        });
        </script>
    <?php elseif ($success || $authorized): ?>
        <div class="registration-message registration-message--success" role="status" tabindex="-1">
            <?php if ($success): ?>
                <strong>Регистрация успешно завершена.</strong>
                <?php if (($arResult['USE_EMAIL_CONFIRMATION'] ?? 'N') === 'Y'): ?>
                    <p>На ваш email отправлено письмо. Перейдите по ссылке в письме, чтобы подтвердить регистрацию.</p>
                <?php elseif (!$authorized): ?>
                    <p>Теперь вы можете войти в свой аккаунт.</p>
                <?php endif; ?>
            <?php else: ?>
                <strong>Вы уже вошли в свой аккаунт.</strong>
            <?php endif; ?>
        </div>
        <?php if ($authorized): ?>
            <a href="/personal/" class="registration-button">Перейти в личный кабинет</a>
        <?php endif; ?>
    <?php else: ?>
        <form method="post" action="<?=POST_FORM_ACTION_URI?>" name="regform" class="registration-form">
            <?=bitrix_sessid_post()?>
            <input type="hidden" name="REGISTER[LOGIN]" value="<?=htmlspecialcharsbx($value('EMAIL'))?>">
            <input type="hidden" name="REGISTER[CONFIRM_PASSWORD]" value="">

            <fieldset class="registration-types">
                <legend class="registration__heading">Тип клиента</legend>
                <?php foreach (['individual', 'business'] as $code):
                    if (!isset($types[$code])) { continue; }
                    $option = $types[$code]; ?>
                    <label class="registration-radio" for="reg-type-<?=htmlspecialcharsbx($code)?>">
                        <input type="radio" name="UF_CLIENT_TYPE" id="reg-type-<?=htmlspecialcharsbx($code)?>"
                            value="<?=(int)$option['ID']?>" data-client-type="<?=htmlspecialcharsbx($code)?>"
                            <?=$selected === (string)$option['ID'] ? 'checked' : ''?> required>
                        <span><?=htmlspecialcharsbx($option['VALUE'])?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <div class="registration-section" data-business-field <?=$isBusiness ? '' : 'hidden'?>>
                <h3 class="registration__heading">Реквизиты компании</h3>
                <?php $renderInput('UF_INN', 'ИНН', 'UF_INN', $propertyValue('UF_INN'), 'text', true, 'off', !$isBusiness, true); ?>
                <button type="button" class="registration-button registration-button--secondary" data-action="fill-by-inn" disabled aria-describedby="reg-inn-hint">Заполнить по ИНН</button>
                <p id="reg-inn-hint" class="registration__hint">Пока заполните реквизиты вручную.</p>
                <?php
                $renderInput('UF_KPP', 'КПП (для организаций)', 'UF_KPP', $propertyValue('UF_KPP'), 'text', false, 'off', !$isBusiness, true);
                $renderInput('UF_BUSINESS_ADDRESS', 'Адрес', 'UF_BUSINESS_ADDRESS', $propertyValue('UF_BUSINESS_ADDRESS'), 'text', true, 'off', !$isBusiness, true);
                $renderInput('WORK_COMPANY', 'Наименование компании / ИП', 'REGISTER[WORK_COMPANY]', $value('WORK_COMPANY'), 'text', true, 'organization', !$isBusiness, true);
                ?>
            </div>

            <div class="registration-section">
                <h3 class="registration__heading">Личные данные</h3>
                <?php
                $renderInput('LAST_NAME', 'Фамилия', 'REGISTER[LAST_NAME]', $value('LAST_NAME'), 'text', true, 'family-name');
                $renderInput('NAME', 'Имя', 'REGISTER[NAME]', $value('NAME'), 'text', true, 'given-name');
                $renderInput('SECOND_NAME', 'Отчество', 'REGISTER[SECOND_NAME]', $value('SECOND_NAME'), 'text', false, 'additional-name');
                $renderInput('EMAIL', 'Email', 'REGISTER[EMAIL]', $value('EMAIL'), 'email', true, 'email');
                $renderInput('PERSONAL_PHONE', 'Телефон', 'REGISTER[PERSONAL_PHONE]', $value('PERSONAL_PHONE'), 'tel', true, 'tel');
                if (!empty($arResult['PHONE_REQUIRED'])) {
                    // При SMS-регистрации передаём этот же телефон как PHONE_NUMBER.
                    echo '<input type="hidden" name="REGISTER[PHONE_NUMBER]" value="">';
                }
                ?>
            </div>
            <?php $renderInput('PASSWORD', 'Пароль', 'REGISTER[PASSWORD]', '', 'password', true, 'new-password'); ?>
            <?php if (!empty($arResult['GROUP_POLICY']['PASSWORD_REQUIREMENTS'])): ?>
                <div class="registration__hint"><?=strip_tags($arResult['GROUP_POLICY']['PASSWORD_REQUIREMENTS'], '<br><b><strong>')?></div>
            <?php endif; ?>

            <?php if (($arResult['USE_CAPTCHA'] ?? 'N') === 'Y'): ?>
                <div class="registration-captcha">
                    <input type="hidden" name="captcha_sid" value="<?=htmlspecialcharsbx($arResult['CAPTCHA_CODE'])?>">
                    <img src="/bitrix/tools/captcha.php?captcha_sid=<?=urlencode($arResult['CAPTCHA_CODE'])?>" width="180" height="40" alt="Код проверки">
                    <?php $renderInput('CAPTCHA', 'Код с картинки', 'captcha_word', '', 'text', true, 'off'); ?>
                </div>
            <?php endif; ?>
            <label class="registration-consent" for="registration-consent">
                <input type="checkbox" id="registration-consent" name="registration_consent" value="Y" required>
                <span>Я даю согласие на обработку персональных данных в соответствии с <a href="/privacy-policy/" target="_blank" rel="noopener">Политикой конфиденциальности</a>.</span>
            </label>
            <button class="registration-button" type="submit" name="register_submit_button" value="Y">Зарегистрироваться</button>
        </form>
    <?php endif; ?>
</div>
