<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace TYPO3Canto\CantoFal\Resource;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Canto\CantoApi\Client;
use TYPO3Canto\CantoApi\ClientOptions;

class CantoClientFactory implements LoggerAwareInterface, SingletonInterface
{
    use LoggerAwareTrait;

    public function createClientFromDriverConfiguration(array $configuration): Client
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if ($typo3Version->getMajorVersion() > 11) {
            $gizzleClient = GeneralUtility::makeInstance(GuzzleClientFactory::class)->getClient();
        } else {
            $gizzleClient = GuzzleClientFactory::getClient();
        }

        $clientOptions = new ClientOptions([
            'cantoName' => $configuration['cantoName'],
            'cantoDomain' => $configuration['cantoDomain'],
            'appId' => $configuration['appId'],
            'appSecret' => $configuration['appSecret'],
            'httpClient' => $gizzleClient,
            'logger' => $this->logger,
        ]);
        return new Client($clientOptions);
    }
}
