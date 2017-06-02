<?php

namespace Akeneo\Pim\Api;

use Akeneo\Pim\Client\ResourceClientInterface;
use Akeneo\Pim\Pagination\PageFactoryInterface;
use Akeneo\Pim\Pagination\ResourceCursorFactoryInterface;

/**
 * API implementation to manage the products.
 *
 * @author    Laurent Petard <laurent.petard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductApi implements ProductApiInterface
{
    const PRODUCTS_URI = 'api/rest/v1/products';
    const PRODUCT_URI = 'api/rest/v1/products/%s';

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
        return $this->resourceClient->getResource(static::PRODUCT_URI, [$code]);
    }

    /**
     * {@inheritdoc}
     */
    public function listPerPage($limit = 10, $withCount = false, array $queryParameters = [])
    {
        $data = $this->resourceClient->getResources(static::PRODUCTS_URI, [], $limit, $withCount, $queryParameters);

        return $this->pageFactory->createPage($data);
    }

    /**
     * {@inheritdoc}
     */
    public function all($pageSize = 10, array $queryParameters = [])
    {
        $queryParameters['pagination_type'] = 'search_after';

        $firstPage = $this->listPerPage($pageSize, false, $queryParameters);

        return $this->cursorFactory->createCursor($pageSize, $firstPage);
    }

    /**
     * {@inheritdoc}
     */
    public function create($code, array $data = [])
    {
        if (array_key_exists('identifier', $data)) {
            throw new \InvalidArgumentException('The parameter "identifier" should not be defined in the data parameter');
        }

        $data['identifier'] = $code;

        return $this->resourceClient->createResource(static::PRODUCTS_URI, [], $data);
    }

    /**
     * {@inheritdoc}
     */
    public function upsert($code, array $data = [])
    {
        return $this->resourceClient->upsertResource(static::PRODUCT_URI, [$code], $data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($code)
    {
        return $this->resourceClient->deleteResource(static::PRODUCT_URI, [$code]);
    }

    /**
     * {@inheritdoc}
     */
    public function upsertList($products)
    {
        return $this->resourceClient->upsertResourceList(static::PRODUCTS_URI, [], $products);
    }
}
