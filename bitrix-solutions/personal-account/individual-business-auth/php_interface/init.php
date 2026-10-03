<?php

$registrationHandler = __DIR__
    . '/include/individual_business_registration.php';

if (is_file($registrationHandler)) {
    require_once $registrationHandler;
}