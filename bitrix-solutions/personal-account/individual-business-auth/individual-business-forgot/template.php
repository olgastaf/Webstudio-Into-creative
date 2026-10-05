<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }
$attempt = $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['AUTH_FORM'] ?? '') === 'Y'
    && ($_POST['TYPE'] ?? '') === 'SEND_PWD';
$result = $attempt ? ($arParams['~AUTH_RESULT'] ?? null) : null;
if (is_string($result) && $result !== '') {
    $result = ['TYPE' => 'ERROR', 'MESSAGE' => $result];
}
$success = is_array($result) && ($result['TYPE'] ?? '') === 'OK';
$error = is_array($result) && !$success ? ($result['MESSAGE'] ?? '') : '';
$error = is_scalar($error) ? (string)$error : '';
$email = $_POST['USER_EMAIL'] ?? '';
$email = is_string($email) ? $email : '';
$open = $attempt || ($_SERVER['REQUEST_METHOD'] !== 'POST' && ($_GET['forgot_password'] ?? '') === 'yes');
?>
<div class="registration password-recovery" data-recovery-open="<?=$open ? 'Y' : 'N'?>">
    <h2 class="registration__title" id="recovery-title">Восстановление пароля</h2>
    <?php if ($success): ?>
        <div class="registration-message registration-message--success" role="status" tabindex="-1">
            <strong>Запрос обработан.</strong>
            <p>Проверьте почту. Если аккаунт с этим email существует и восстановление доступно, вы получите письмо с инструкцией. Проверьте также папку «Спам».</p>
        </div>
    <?php else: ?>
        <?php if ($error !== ''):
            $error = preg_replace('~<br\s*/?>~i', "\n", $error); ?>
            <div class="registration-message registration-message--error" role="alert" tabindex="-1"><?=nl2br(htmlspecialcharsbx(htmlspecialcharsback(strip_tags($error))))?></div>
        <?php endif; ?>
        <p class="registration__description">Укажите email вашего аккаунта. Мы отправим инструкцию для смены пароля.</p>
        <form name="password_recovery_form" class="password-recovery-form" method="post" action="<?=$arResult['AUTH_URL']?>">
            <?=bitrix_sessid_post()?>
            <input type="hidden" name="AUTH_FORM" value="Y">
            <input type="hidden" name="TYPE" value="SEND_PWD">
            <input type="hidden" name="USER_LOGIN" value="">
            <div class="registration-field">
                <input class="registration-field__input" type="email" id="recovery-email" name="USER_EMAIL" value="<?=htmlspecialcharsbx($email)?>" placeholder=" " autocomplete="email" required>
                <label class="registration-field__label" for="recovery-email">Email *</label>
            </div>
            <?php if (!empty($arResult['USE_CAPTCHA']) && $arResult['USE_CAPTCHA'] !== 'N'): ?>
                <div class="registration-captcha">
                    <input type="hidden" name="captcha_sid" value="<?=htmlspecialcharsbx($arResult['CAPTCHA_CODE'])?>">
                    <img src="/bitrix/tools/captcha.php?captcha_sid=<?=urlencode($arResult['CAPTCHA_CODE'])?>" width="180" height="40" alt="Код проверки">
                    <div class="registration-field">
                        <input class="registration-field__input" type="text" id="recovery-captcha" name="captcha_word" placeholder=" " maxlength="50" autocomplete="off" required>
                        <label class="registration-field__label" for="recovery-captcha">Код с картинки *</label>
                    </div>
                </div>
            <?php endif; ?>
            <button class="registration-button" type="submit" name="send_account_info" value="Y">Отправить инструкцию</button>
        </form>
    <?php endif; ?>
    <div class="authorization-links password-recovery__back">
        <a href="#login-panel" data-auth-view="login">Вернуться ко входу</a>
    </div>
</div>
