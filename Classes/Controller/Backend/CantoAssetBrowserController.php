<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace TYPO3Canto\CantoFal\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Canto\CantoApi\Endpoint\Authorization\AuthorizationFailedException;
use TYPO3Canto\CantoApi\Http\Asset\SearchRequest;
use TYPO3Canto\CantoFal\Domain\Model\Dto\AssetSearch;
use TYPO3Canto\CantoFal\Pagination\SearchResultPaginator;
use TYPO3Canto\CantoFal\Resource\Driver\CantoDriver;
use TYPO3Canto\CantoFal\Resource\NoCantoStorageException;
use TYPO3Canto\CantoFal\Resource\Repository\CantoRepository;
use TYPO3Canto\CantoFal\Resource\Repository\Exception\InvalidSearchTypeException;
use TYPO3Canto\CantoFal\Utility\CantoUtility;

class CantoAssetBrowserController
{
    protected StorageRepository $storageRepository;

    public function __construct(StorageRepository $storageRepository)
    {
        $this->storageRepository = $storageRepository;
    }

    /**
     * @throws NoCantoStorageException
     * @throws AuthorizationFailedException
     * @throws InvalidSearchTypeException
     */
    public function search(ServerRequestInterface $request): ResponseInterface
    {
        $storageUid = (int)$request->getQueryParams()['storageUid'];
        $page = max((int)$request->getQueryParams()['page'], 1);
        $storage = $this->getCantoStorageByUid($storageUid);
        $cantoRepository = $this->getCantoRepository($storage);
        $search = $this->buildAssetSearchObject($request);
        $paginator = new SearchResultPaginator($search, $cantoRepository, $page);

        $view = $this->initializeView();
        $view->setTemplate('SearchResults');
        $view->assignMultiple([
            'results' => $paginator,
            'pagination' => new SimplePagination($paginator),
            'queryParams' => $request->getQueryParams(),
            'isMdcEnabled' => CantoUtility::isMdcActivated($storage->getConfiguration()),
        ]);

        $response = new Response();
        $response->getBody()->write($view->render());
        return $response;
    }

    public function importFile(ServerRequestInterface $request): ResponseInterface
    {
        return $this->buildFileFetchingResponse($request);
    }

    private function buildFileFetchingResponse(ServerRequestInterface $request): ResponseInterface
    {
        $storageUid = (int)($request->getQueryParams()['storageUid'] ?? 0);
        $scheme = $request->getQueryParams()['scheme'] ?? '';
        $identifier = $request->getQueryParams()['identifier'] ?? '';
        $storage = $this->getCantoStorageByUid($storageUid);

        if ($scheme && $identifier) {
            $combinedFileIdentifier = CantoUtility::buildCombinedIdentifier($scheme, $identifier);

            $file = $storage->getFile($combinedFileIdentifier);
            if ($file instanceof File) {
                return new JsonResponse([
                    'fileUid' => $file->getUid(),
                    'fileName' => $file->getName(),
                ]);
            }
        }
        // Todo Add error message what went wrong.
        return new Response(null, 400);
    }

    /**
     * @throws InvalidSearchTypeException
     */
    protected function buildAssetSearchObject(ServerRequestInterface $request): AssetSearch
    {
        $search = new AssetSearch();
        $searchType = $request->getQueryParams()['search']['type'] ?? '';
        $allowedFileExtensions = $request->getQueryParams()['allowedFileExtensions'] ?? '';
        if ($allowedFileExtensions) {
            $search->setKeyword(implode('|', array_map(
                static fn(string $fileExtension) => '.' . trim($fileExtension),
                explode(',', $allowedFileExtensions)
            )));
        } else {
            $search->setSchemes([
                SearchRequest::SCHEME_IMAGE,
                SearchRequest::SCHEME_VIDEO,
                SearchRequest::SCHEME_AUDIO,
                SearchRequest::SCHEME_DOCUMENT,
                SearchRequest::SCHEME_PRESENTATION,
                SearchRequest::SCHEME_OTHER,
            ]);
        }

        // TODO We cannot use keyword search and file extension filter because of missing support for logical grouping.
        switch ((string)$searchType) {
            case 'identifier':
                $identifier = (string)($request->getQueryParams()['search']['identifier'] ?? '');
                $scheme = (string)($request->getQueryParams()['search']['scheme'] ?? '');
                $search->setIdentifier($identifier);
                $search->setScheme($scheme);
                break;
            case 'categories':
                $searchQuery = (string)($request->getQueryParams()['search']['query'] ?? '');
                $search->setCategories($searchQuery);
                break;
            case 'tags':
                $searchQuery = (string)($request->getQueryParams()['search']['query'] ?? '');
                $search->setTags($searchQuery);
                break;
            default:
                throw new InvalidSearchTypeException(
                    sprintf('Invalid search type %s given.', $searchType),
                    1629119913
                );
        }

        return $search;
    }

    /**
     * @throws NoCantoStorageException
     */
    protected function getCantoStorageByUid(int $uid): ResourceStorage
    {
        $storage = $this->storageRepository->findByUid($uid);
        if ($storage === null || $storage->getDriverType() !== CantoDriver::DRIVER_NAME) {
            throw new NoCantoStorageException('The given storage is not a canto storage.', 1628166504);
        }
        return $storage;
    }

    /**
     * @throws AuthorizationFailedException
     */
    protected function getCantoRepository(ResourceStorage $storage): CantoRepository
    {
        $cantoRepository = GeneralUtility::makeInstance(CantoRepository::class);
        $cantoRepository->initialize($storage->getUid(), $storage->getConfiguration());
        return $cantoRepository;
    }

    protected function initializeView(): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([
            100 => 'EXT:canto_fal/Resources/Private/Layouts/',
        ]);
        $view->setPartialRootPaths([
            100 => 'EXT:canto_fal/Resources/Private/Partials/',
        ]);
        $view->setTemplateRootPaths([
            100 => 'EXT:canto_fal/Resources/Private/Templates/CantoAssetBrowser/Ajax/',
        ]);
        return $view;
    }
}
