<?php
namespace Tulip;

/**
 * Tulip Application Core & Security Context
 */
class Bootstrap {
    public static function init(): void {
        require_once dirname(__DIR__) . '/config/database.php';
        require_once dirname(__DIR__) . '/config/SecurityHeaders.php';
    }
}
