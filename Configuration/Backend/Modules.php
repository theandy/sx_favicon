<?php

// EXT:sx_favicon/Configuration/Backend/Modules.php
use AndreasLoewer\SxFavicon\Controller\ConfigController;

return [
    'site_favicons' => [
        'parent' => 'site',
        'access' => 'admin',
        'path' => '/module/site/favicons',
        'iconIdentifier' => 'module-sx-favicon',
        'labels' => 'LLL:EXT:sx_favicon/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => ['target' => ConfigController::class . '::index'],
            'save'     => ['path' => '/save', 'target' => ConfigController::class . '::save'],
        ],
    ],
];
