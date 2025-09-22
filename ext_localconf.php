<?php
defined('TYPO3') || die();

call_user_func(function () {
    // DEBUG: Marker – ext_localconf wird ausgeführt
    error_log('[sx_favicon] ext_localconf start');

    // FE Middleware …
    $GLOBALS['TYPO3_CONF_VARS']['FE']['middlewares']['sx_favicon/favicon'] = [
        'target' => \AndreasLoewer\SxFavicon\Middleware\FaviconMiddleware::class,
        'after' => ['typo3/cms-frontend/base-redirect-resolver'],
        'before' => ['typo3/cms-frontend/static-route-resolver'],
    ];

    try {

    } catch (\Throwable $e) {
        // error_log('[sx_favicon] registerModule FAILED: ' . $e->getMessage());
    }

    // Fluid ViewHelper Namespace
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['sxFavicon'][] =
        'AndreasLoewer\\SxFavicon\\ViewHelpers';
    error_log('[sx_favicon] ext_localconf end');
});
