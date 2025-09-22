<?php
declare(strict_types=1);

use AndreasLoewer\SxFavicon\Controller\ConfigController;

return [
    'site_favicons' => [
        'parent' => 'site',                    // Site Management
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/site/favicons',
        'iconIdentifier' => 'module-sx-favicon',
        'labels' => 'LLL:EXT:sx_favicon/Resources/Private/Language/locallang_mod.xlf',

        // nur Symfony-Routen, KEIN Extbase
        'routes' => [
            '_default' => [
                'target' => ConfigController::class . '::index',
            ],
            'save' => [
                'path'   => '/save',
                'target' => ConfigController::class . '::save',
            ],
        ],
    ],
];
