<?php
// Фрагмент для страницы с подключённым прологом Битрикса.
if (!defined('INDIVIDUAL_BUSINESS_REGISTRATION')) {
    define('INDIVIDUAL_BUSINESS_REGISTRATION', true);
}

// Компонент проверяет LOGIN и CONFIRM_PASSWORD до событий создания.
// Поэтому формируем их на сервере ДО вызова компонента.
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['register_submit_button'])
    && is_array($_POST['REGISTER'] ?? null)
) {
    $email = $_POST['REGISTER']['EMAIL'] ?? '';
    $password = $_POST['REGISTER']['PASSWORD'] ?? '';
    $email = is_string($email) ? trim($email) : '';
    $password = is_string($password) ? $password : '';

    $_POST['REGISTER']['EMAIL'] = $email;
    $_POST['REGISTER']['LOGIN'] = $email;
    $_POST['REGISTER']['CONFIRM_PASSWORD'] = $password;
    $phone = $_POST['REGISTER']['PERSONAL_PHONE'] ?? '';
    if (COption::GetOptionString('main', 'new_user_phone_auth', 'N') === 'Y'
        && COption::GetOptionString('main', 'new_user_phone_required', 'N') === 'Y') {
        $_POST['REGISTER']['PHONE_NUMBER'] = is_string($phone) ? trim($phone) : '';
    }
    // Эта версия компонента читает $_REQUEST.
    $_REQUEST['REGISTER'] = $_POST['REGISTER'];
}
// После POST открываем именно ту панель, которая была отправлена.
$registrationAttempt = $_SERVER['REQUEST_METHOD'] === 'POST'
    && (isset($_POST['register_submit_button']) || isset($_POST['code_submit_button']));
$authenticationAttempt = $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['AUTH_FORM'] ?? '') === 'Y'
    && in_array($_POST['TYPE'] ?? '', ['AUTH', 'OTP'], true);
$recoveryAttempt = $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['AUTH_FORM'] ?? '') === 'Y'
    && ($_POST['TYPE'] ?? '') === 'SEND_PWD';
$recoveryView = !$registrationAttempt && ($recoveryAttempt
    || ($_SERVER['REQUEST_METHOD'] !== 'POST' && ($_GET['forgot_password'] ?? '') === 'yes'));
// Подстраховка, если успешный вход обработан без перехода в прологе.
if ($authenticationAttempt && $USER->IsAuthorized()) {
    LocalRedirect('/personal/');
}
?>
<button type="button" class="btn btn-primary" id="open-auth" data-auth-view="login">Войти</button>
<button type="button" class="btn btn-primary" id="open-register" data-auth-view="registration">Регистрация</button>
<div id="register-modal" class="register-modal" hidden>
    <div class="register-modal__overlay" data-registration-close></div>
    <div class="register-modal__box" role="dialog" aria-modal="true" aria-labelledby="authorization-title" tabindex="-1">
        <button type="button" class="register-modal__close" data-registration-close aria-label="Закрыть">&times;</button>
        <div id="login-panel" data-auth-panel="login" <?=($registrationAttempt || $recoveryView) ? 'hidden' : ''?>>
            <?php $APPLICATION->IncludeComponent(
                'bitrix:system.auth.form',
                'individual-business-auth',
                [
                    'REGISTER_URL' => '/auth/',
                    'FORGOT_PASSWORD_URL' => '/auth/',
                    'PROFILE_URL' => '/personal/',
                    'SHOW_ERRORS' => 'Y',
                ],
                false
            ); ?>
        </div>
        <div id="recovery-panel" data-auth-panel="forgot" <?=$recoveryView ? '' : 'hidden'?>>
            <?php $APPLICATION->IncludeComponent(
                'bitrix:system.auth.forgotpasswd',
                'individual-business-forgot',
                [
                    'AUTH_RESULT' => $recoveryAttempt ? ($APPLICATION->arAuthResult ?? null) : null,
                ],
                false
            ); ?>
        </div>
        <div id="registration-panel" data-auth-panel="registration" <?=$registrationAttempt ? '' : 'hidden'?>>
            <?php
            $APPLICATION->IncludeComponent(
                'bitrix:main.register',
                'individual-business-reg',
                [
                    'SHOW_FIELDS' => ['LAST_NAME', 'NAME', 'SECOND_NAME', 'EMAIL', 'PERSONAL_PHONE', 'WORK_COMPANY'],
                    'USER_PROPERTY' => ['UF_CLIENT_TYPE', 'UF_INN', 'UF_KPP', 'UF_BUSINESS_ADDRESS'],
                    'REQUIRED_FIELDS' => ['LAST_NAME', 'NAME', 'EMAIL', 'PERSONAL_PHONE'],
                    'AUTH' => 'Y',
                    'USE_BACKURL' => 'N',
                    'SUCCESS_PAGE' => '',
                    'SET_TITLE' => 'N',
                ],
                false
            );
            ?>
        </div>
    </div>
</div>
