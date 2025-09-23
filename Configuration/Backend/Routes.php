<?php
declare(strict_types=1);

use AndreasLoewer\SxFavicon\Controller\ConfigController;

return [
    // Haupt-Route des Moduls
    'sx_favicon.module' => [
        'path'   => '/module/site/sxfv',
        'target' => ConfigController::class . '::index',
    ],

    // Save-Route
    'sx_favicon.module.save' => [
        'path'   => '/module/site/sxfv/save',
        'target' => ConfigController::class . '::save',
    ],
];
