<?php

namespace Akeneo\Pim\Api;

use Akeneo\Pim\Client\ResourceClientInterface;
use Akeneo\Pim\Pagination\PageFactoryInterface;
use Akeneo\Pim\Pagination\ResourceCursorFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * API implementation to manage the media files for the products.
 *
 * @author    Laurent Petard <laurent.petard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductMediaFileApi implements MediaFileApiInterface
{
    const MEDIA_FILES_URI = 'api/rest/v1/media-files';
    const MEDIA_FILE_URI = 'api/rest/v1/media-files/%s';
    const MEDIA_FILE_DOWNLOAD_URI = 'api/rest/v1/media-files/%s/download';
    const MEDIA_FILE_URI_CODE_REGEX = '~/api/rest/v1/media\-files/(?P<code>.*)$~';

    /** @var ResourceClientInterface */
    protected $resourceClient;

    /** @var PageFactoryInterface */
    protected $pageFactory;

    /** @var ResourceCursorFactoryInterface */
    protected $cursorFactory;

    /**
     * @param ResourceClientInterface        $resourceClient
     * @param PageFactoryInterface           $pageFactory
     * @param ResourceCursorFactoryInterface $cursorFactory
     */
    public function __construct(
        ResourceClientInterface $resourceClient,
        PageFactoryInterface $pageFactory,
        ResourceCursorFactoryInterface $cursorFactory
    ) {
        $this->resourceClient = $resourceClient;
        $this->pageFactory = $pageFactory;
        $this->cursorFactory = $cursorFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function get($code)
    {
        return $this->resourceClient->getResource(static::MEDIA_FILE_URI, [$code]);
    }

    /**
     * {@inheritdoc}
     */
    public function listPerPage($limit = 10, $withCount = false, array $queryParameters = [])
    {
        $data = $this->resourceClient->getResources(static::MEDIA_FILES_URI, [], $limit, $withCount, $queryParameters);

        return $this->pageFactory->createPage($data);
    }

    /**
     * {@inheritdoc}
     */
    public function all($pageSize = 10, array $queryParameters = [])
    {
        $firstPage = $this->listPerPage($pageSize, false, $queryParameters);

        return $this->cursorFactory->createCursor($pageSize, $firstPage);
    }

    /**
     * {@inheritdoc}
     */
    public function create($mediaFile, array $productData)
    {
        if (is_string($mediaFile)) {
            if (!is_readable($mediaFile)) {
                throw new \RuntimeException(sprintf('The file "%s" could not be read.', $mediaFile));
            }

            $mediaFile = fopen($mediaFile, 'rb');
        }

        $requestParts = [
            [
                'name' => 'product',
                'contents' => json_encode($productData),
            ],
            [
                'name' => 'file',
                'contents' => $mediaFile,
            ]
        ];

        $response = $this->resourceClient->createMultipartResource(static::MEDIA_FILES_URI, [], $requestParts);

        return $this->extractCodeFromCreationResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function download($code)
    {
        return $this->resourceClient->getStreamedResource(static::MEDIA_FILE_DOWNLOAD_URI, [$code]);
    }

    /**
     * Extracts the code of a media-file from a creation response.
     *
     * @param ResponseInterface $response
     *
     * @throws \RuntimeException if unable to extract the code
     *
     * @return mixed
     */
    protected function extractCodeFromCreationResponse(ResponseInterface $response)
    {
        $headers = $response->getHeaders();

        if (!isset($headers['Location'][0])) {
            throw new \RuntimeException('The response does not contain the URI of the created media-file.');
        }

        $matches = [];
        if (1 !== preg_match(static::MEDIA_FILE_URI_CODE_REGEX, $headers['Location'][0], $matches)) {
            throw new \RuntimeException('Unable to find the code in the URI of the created media-file.');
        }

        return $matches['code'];
    }
}
