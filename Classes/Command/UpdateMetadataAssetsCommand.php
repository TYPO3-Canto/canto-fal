<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace TYPO3Canto\CantoFal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\Service\ExtractorService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Canto\CantoFal\Resource\Driver\CantoDriver;

final class UpdateMetadataAssetsCommand extends Command
{
    private ExtractorService $extractorService;

    private FileRepository $fileRepository;

    private FileIndexRepository $fileIndexRepository;

    private FrontendInterface $cantoFileCache;

    public function __construct(
        ExtractorService $extractorService,
        FileRepository $fileRepository,
        FileIndexRepository $fileIndexRepository,
        FrontendInterface $cantoFileCache
    ) {
        parent::__construct();
        $this->extractorService = $extractorService;
        $this->fileRepository = $fileRepository;
        $this->fileIndexRepository = $fileIndexRepository;
        $this->cantoFileCache = $cantoFileCache;
    }

    protected function configure(): void
    {
        $this->setDescription('Update Metadata for all integrated canto assets.');
        $this->setHelp(
            <<<'EOF'
This command will pull down all metadata and override it analog to the definition in the backend.
It will also delete all processed files to these files
EOF
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $counter = 0;
        $indexerArray = [];

        $files = $this->fileRepository->findAll();
        $this->cantoFileCache->flush();
        foreach ($files as $file) {
            assert($file instanceof File);
            if ($file->getStorage()->getDriverType() !== CantoDriver::DRIVER_NAME) {
                continue;
            }

            $output->writeln('Working on File: ' . $file->getIdentifier() . ' - ' . $file->getName());

            try {
                $storage = $file->getStorage();
                $storageUid = $storage->getUid();
                $currentEvaluatePermissions = $storage->getEvaluatePermissions();
                $storage->setEvaluatePermissions(false);

                $indexer = $indexerArray[$storageUid] = $indexerArray[$storageUid] ?? GeneralUtility::makeInstance(Indexer::class, $storage);

                $file = $indexer->updateIndexEntry($file);

                if (!$storage->autoExtractMetadataEnabled()) {
                    $file->getMetaData()->add($this->extractorService->extractMetaData($file))->save();
                }

                $storage->setEvaluatePermissions($currentEvaluatePermissions);
            } catch (\Exception $e) {
                $this->fileIndexRepository->markFileAsMissing($file->getUid());
                $output->writeln(sprintf(
                    '     Error on File: %s - %s -> %s (%s)',
                    $file->getIdentifier(),
                    $file->getName(),
                    $e->getMessage(),
                    $e->getCode()
                ));
                continue;
            }
            if (++$counter > 1000) {
                $counter = 0;
                // to circumvent API limits we need to pause for 60s after processing a thousand requests
                sleep(60);
            }
        }

        return self::SUCCESS;
    }
}
