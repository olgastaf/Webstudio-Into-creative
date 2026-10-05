<?php
// Дополнительная стандартная страница восстановления/смены пароля.
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Восстановление доступа');
$APPLICATION->AuthForm('', false, false, 'N', false);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
