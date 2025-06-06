<?php

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    'canto_assset_browser' => [
        'path' => '/canto-fal/canto-asset-browser',
        'access' => 'public',
        'target' => \TYPO3Canto\CantoFal\Controller\Backend\CantoAssetBrowserController::class . '::mainAction',
    ],
];
