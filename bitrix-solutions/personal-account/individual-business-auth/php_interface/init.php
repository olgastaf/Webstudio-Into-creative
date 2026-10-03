<?php

$registrationHandler = __DIR__
    . '/include/individual_business_reg.php';

if (is_file($registrationHandler)) {
    require_once $registrationHandler;
}