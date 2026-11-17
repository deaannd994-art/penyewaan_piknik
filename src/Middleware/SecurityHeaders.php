<?php
namespace Tulip\Middleware;

require_once dirname(__DIR__, 2) . '/config/SecurityHeaders.php';

/**
 * SecurityHeaders Middleware Wrapper
 * Standard: OWASP Top 10 & Helmet Security Headers
 */
class SecurityHeaders extends \SecurityHeaders {
    // Inherits apply() and applyJson() with Google Maps frame-src compliance
}
