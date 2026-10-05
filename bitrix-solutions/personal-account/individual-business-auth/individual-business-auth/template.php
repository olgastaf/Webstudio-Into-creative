<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }
CJSCore::Init();
$otp = ($arResult['FORM_TYPE'] ?? '') === 'otp';
$logout = ($arResult['FORM_TYPE'] ?? '') === 'logout';
$attempt = $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['AUTH_FORM'] ?? '') === 'Y'
    && in_array($_POST['TYPE'] ?? '', ['AUTH', 'OTP'], true);
$error = ($attempt || $otp) ? ($arResult['ERROR_MESSAGE'] ?? '') : '';
if (is_array($error)) { $error = $error['MESSAGE'] ?? ''; }
$error = is_scalar($error) ? (string)$error : '';
$email = $_POST['USER_LOGIN'] ?? ($arResult['~USER_LOGIN'] ?? '');
$email = is_string($email) ? $email : '';
$formName = 'system_auth_form' . $arResult['RND'];
?>
<div class="registration authorization" data-authentication-open="<?=($attempt || $otp) ? 'Y' : 'N'?>">
    <h2 class="registration__title" id="authorization-title"><?=$otp ? 'Подтверждение входа' : 'Вход в личный кабинет'?></h2>
    <?php if ($error !== ''):
        $error = preg_replace('~<br\s*/?>~i', "\n", $error); ?>
        <div class="registration-message registration-message--error" role="alert" tabindex="-1"><?=nl2br(htmlspecialcharsbx(htmlspecialcharsback(strip_tags($error))))?></div>
    <?php endif; ?>
    <?php if ($logout): ?>
        <p>Вы уже вошли в свой аккаунт.</p>
        <a class="registration-button" href="/personal/">Перейти в личный кабинет</a>
    <?php else: ?>
        <form class="authorization-form" name="<?=htmlspecialcharsbx($formName)?>" method="post" action="<?=$arResult['AUTH_URL']?>">
            <?=bitrix_sessid_post()?>
            <input type="hidden" name="AUTH_FORM" value="Y">
            <input type="hidden" name="TYPE" value="<?=$otp ? 'OTP' : 'AUTH'?>">
            <input type="hidden" name="backurl" value="/personal/">
            <?php if ($otp): ?>
                <p class="registration__description">Введите одноразовый код из приложения аутентификации.</p>
                <div class="registration-field">
                    <input class="registration-field__input" type="text" id="auth-otp" name="USER_OTP" placeholder=" " autocomplete="one-time-code" inputmode="numeric" required>
                    <label class="registration-field__label" for="auth-otp">Одноразовый код *</label>
                </div>
            <?php else: ?>
                <div class="registration-field">
                    <input class="registration-field__input" type="email" id="auth-email" name="USER_LOGIN" value="<?=htmlspecialcharsbx($email)?>" placeholder=" " autocomplete="username" required>
                    <label class="registration-field__label" for="auth-email">Email *</label>
                </div>
                <div class="registration-field">
                    <input class="registration-field__input" type="password" id="auth-password" name="USER_PASSWORD" placeholder=" " autocomplete="current-password" required>
                    <label class="registration-field__label" for="auth-password">Пароль *</label>
                </div>
            <?php endif; ?>
            <?php if (!empty($arResult['CAPTCHA_CODE'])): ?>
                <div class="registration-captcha">
                    <input type="hidden" name="captcha_sid" value="<?=htmlspecialcharsbx($arResult['CAPTCHA_CODE'])?>">
                    <img src="/bitrix/tools/captcha.php?captcha_sid=<?=urlencode($arResult['CAPTCHA_CODE'])?>" width="180" height="40" alt="Код проверки">
                    <div class="registration-field">
                        <input class="registration-field__input" type="text" id="auth-captcha" name="captcha_word" placeholder=" " autocomplete="off" maxlength="50" required>
                        <label class="registration-field__label" for="auth-captcha">Код с картинки *</label>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!$otp && ($arResult['STORE_PASSWORD'] ?? 'N') === 'Y'): ?>
                <label class="registration-consent" for="auth-remember">
                    <input type="checkbox" id="auth-remember" name="USER_REMEMBER" value="Y" <?=($_POST['USER_REMEMBER'] ?? '') === 'Y' ? 'checked' : ''?>>
                    <span>Запомнить меня</span>
                </label>
            <?php elseif ($otp && ($arResult['REMEMBER_OTP'] ?? 'N') === 'Y'): ?>
                <label class="registration-consent" for="auth-remember-otp">
                    <input type="checkbox" id="auth-remember-otp" name="OTP_REMEMBER" value="Y">
                    <span>Запомнить этот браузер</span>
                </label>
            <?php endif; ?>
            <button class="registration-button" type="submit" name="Login" value="Y"><?=$otp ? 'Подтвердить' : 'Войти'?></button>
            <div class="authorization-links">
                <?php if ($otp): ?>
                    <a href="<?=$arResult['AUTH_LOGIN_URL']?>">Ввести email и пароль заново</a>
                <?php else: ?>
                    <a href="<?=$arResult['AUTH_FORGOT_PASSWORD_URL']?>" data-auth-view="forgot">Забыли пароль?</a>
                    <?php if (($arResult['NEW_USER_REGISTRATION'] ?? 'N') === 'Y'): ?>
                        <a href="#registration-panel" data-auth-view="registration">Зарегистрироваться</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</div>
