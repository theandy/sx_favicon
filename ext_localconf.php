<?php
defined('TYPO3') || die();
error_log('[sx_favicon] ext_localconf was executed');

call_user_func(function () {
    // FE Middleware: stabile Root-Pfade wie /favicon.ico bedienen
    $GLOBALS['TYPO3_CONF_VARS']['FE']['middlewares']['sx_favicon/favicon'] = [
        'target' => \AndreasLoewer\SxFavicon\Middleware\FaviconMiddleware::class,
        'after' => ['typo3/cms-frontend/base-redirect-resolver'],
        'before' => ['typo3/cms-frontend/static-route-resolver'],
    ];

    // Backend-Modul (unter Site Management)
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'SxFavicon',
        'site',
        'favicons',
        '',
        [\AndreasLoewer\SxFavicon\Controller\ConfigController::class => 'index,save'],
        [
            'access' => 'admin',
            'icon' => 'EXT:sx_favicon/Resources/Public/Icons/module.svg',
            'labels' => 'LLL:EXT:sx_favicon/Resources/Private/Language/locallang_mod.xlf'
        ]
    );

    // Fluid ViewHelper Namespace
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['sxFavicon'][] =
        'AndreasLoewer\\SxFavicon\\ViewHelpers';
});
