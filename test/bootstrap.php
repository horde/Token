<?php
/**
 * Bootstrap file for PHPUnit tests
 *
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

// Composer autoloader
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    throw new RuntimeException(
        'Run "composer install" in the Token directory to set up test dependencies.'
    );
}
require_once $autoload;

// Load legacy test case if Horde_Test is available
if (class_exists('Horde_Test_Case')) {
    // Legacy BackendTestCase needs this
    require_once __DIR__ . '/Unit/Legacy/BackendTestCase.php';
}
