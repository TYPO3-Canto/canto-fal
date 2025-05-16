<?php

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    'import_canto_file' => [
        'path' => '/canto-fal/import-canto-file',
        'access' => 'public',
        'target' => \TYPO3Canto\CantoFal\Controller\Backend\CantoAssetBrowserController::class . '::importFile',
    ],
    'add_canto_cdn_file' => [
        'path' => '/canto-fal/import-canto-cdn-file',
        'access' => 'public',
        'target' => \TYPO3Canto\CantoFal\Controller\Backend\CantoAssetBrowserController::class . '::importCdn',
    ],
    'search_canto_file' => [
        'path' => '/canto-fal/search-canto-file',
        'access' => 'public',
        'target' => \TYPO3Canto\CantoFal\Controller\Backend\CantoAssetBrowserController::class . '::search',
    ],
];
