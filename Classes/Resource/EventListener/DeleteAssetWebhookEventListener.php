<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace TYPO3Canto\CantoFal\Resource\EventListener;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3Canto\CantoFal\Resource\Driver\CantoDriver;
use TYPO3Canto\CantoFal\Resource\Event\IncomingWebhookEvent;
use TYPO3Canto\CantoFal\Utility\CantoUtility;

final class DeleteAssetWebhookEventListener
{
    private ResourceFactory $resourceFactory;
    private StorageRepository $storageRepository;
    private ProcessedFileRepository $processedFileRepository;

    public function __construct(ResourceFactory $resourceFactory, StorageRepository $storageRepository, ProcessedFileRepository $processedFileRepository)
    {
        $this->resourceFactory = $resourceFactory;
        $this->storageRepository = $storageRepository;
        $this->processedFileRepository = $processedFileRepository;
    }

    public function __invoke(IncomingWebhookEvent $event)
    {
        if ($event->getType() !== IncomingWebhookEvent::ASSET_DELETION) {
            return;
        }
        $cantoStorages = $this->storageRepository->findByStorageType(CantoDriver::DRIVER_NAME);
        $identifier = CantoUtility::buildCombinedIdentifier($event->getScheme(), $event->getId());
        $file = $this->getFile($cantoStorages, $identifier);
        if ($file !== null) {
            foreach ($this->processedFileRepository->findAllByOriginalFile($file) as $processedFile) {
                $processedFile->delete(true);
            }
            $file->delete();
        }
    }

    /**
     * @param ResourceStorage[] $storages
     * @param string $identifier
     * @return File|null
     */
    private function getFile(array $storages, string $identifier): ?File
    {
        foreach ($storages as $storage) {
            $file = $this->resourceFactory->getFileObjectByStorageAndIdentifier($storage->getUid(), $identifier);
            if ($file instanceof File) {
                return $file;
            }
        }
        return null;
    }
}
