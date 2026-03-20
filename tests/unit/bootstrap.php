<?php

/**
 * PHPUnit bootstrap for LivingWord unit tests.
 *
 * @package    Livingword.Tests
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Ensure the Composer autoloader is available
$autoloader = __DIR__ . '/../../libraries/vendor/autoload.php';

if (!file_exists($autoloader)) {
    echo "Composer autoloader not found. Run 'composer install' first.\n";
    exit(1);
}

require_once $autoloader;

// Define Joomla constants if not already set (for standalone test runs)
if (!\defined('_JEXEC')) {
    \define('_JEXEC', 1);
}

if (!\defined('JPATH_BASE')) {
    \define('JPATH_BASE', \dirname(__DIR__, 2));
}

if (!\defined('JPATH_ROOT')) {
    \define('JPATH_ROOT', JPATH_BASE);
}

if (!\defined('JPATH_ADMINISTRATOR')) {
    \define('JPATH_ADMINISTRATOR', JPATH_BASE . '/admin');
}

if (!\defined('JPATH_SITE')) {
    \define('JPATH_SITE', JPATH_BASE . '/site');
}
