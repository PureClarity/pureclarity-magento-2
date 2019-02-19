<?php
namespace Pureclarity\Core\Adapter;
 
use Magento\CatalogSearch\Helper\Data;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Search\Adapter\Mysql\Aggregation\Builder as AggregationBuilder;
use Magento\Framework\Search\Adapter\Mysql\DocumentFactory;
use Magento\Framework\Search\Adapter\Mysql\Mapper;
use Magento\Framework\Search\Adapter\Mysql\ResponseFactory;
use Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory;
use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\ProductList\Toolbar;

class Search implements AdapterInterface
{
    protected $mapper;
    protected $responseFactory;
    private $connectionManager;
    private $aggregationBuilder;
    private $temporaryStorageFactory;
    protected $catalogSearchHelper;
    protected $storeManager;
    protected $request;
    protected $documentFactory;
    protected $logger;
    protected $service;
    protected $coreHelper;
    protected $toolBar;
    protected $pureClarityMapper;

    public function __construct(
        Mapper $mapper,
        \Pureclarity\Core\Model\Mapper $pureClarityMapper,
        ResponseFactory $responseFactory,
        ResourceConnection $connectionManager,
        AggregationBuilder $aggregationBuilder,
        TemporaryStorageFactory $temporaryStorageFactory,
        Data $catalogSearchHelper,
        StoreManagerInterface $storeManager,
        Http $request,
        DocumentFactory $documentFactory,
        Toolbar $toolBar,
        \Psr\Log\LoggerInterface $logger,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Pureclarity\Core\Helper\Service $service
    ) {
        $this->mapper = $mapper;
        $this->responseFactory = $responseFactory;
        $this->connectionManager = $connectionManager;
        $this->aggregationBuilder = $aggregationBuilder;
        $this->temporaryStorageFactory = $temporaryStorageFactory;
        $this->catalogSearchHelper = $catalogSearchHelper;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->documentFactory = $documentFactory;
        $this->logger = $logger;
        $this->service = $service;
        $this->coreHelper = $coreHelper;
        $this->toolBar = $toolBar;
        $this->pureClarityMapper = $pureClarityMapper;
    }

    /**
     * @param RequestInterface $request
     * @return QueryResponse
     */
    public function query(RequestInterface $request)
    {
        $temporaryStorage = $this->temporaryStorageFactory->create();
        $documents = [];
        $table = null;
        
        
        if ($this->coreHelper->isSearchActive() &&
            ($this->coreHelper->isServerSide() || $this->coreHelper->seoSearchFriendly()) &&
            (($this->isCategoryPage() && $this->getCategoryId($request) && $this->coreHelper->isProdListingActive()) || $this->isSearchPage())) {
            $this->service->dispatch();
            $result = $this->service->getSearchResult();
            
            if ($result) {
                $products = $result['products'];
                $productCount = sizeof($products);
                
                foreach ($products as $product) {
                    $id = $product['Id'];
                    if ($id) {
                        $documents[$id] = [
                            "entity_id" => $id,
                            "score" => $productCount
                        ];
                        $productCount = $productCount-1;
                    }
                }

                $getDocumentMethod = 'getDocument21';
                $storeDocumentsMethod = 'storeApiDocuments';
                if (version_compare($this->getMagentoVersion(), '2.1.0', '<') === true) {
                    $getDocumentMethod = 'getDocument20';
                    $storeDocumentsMethod = 'storeDocuments';
                }

                $apiDocuments = array_map(function ($document) use ($getDocumentMethod) {
                    return $this->{$getDocumentMethod}($document);
                }, $documents);

                $pcResultTable = $temporaryStorage->{$storeDocumentsMethod}($apiDocuments);
                
                $query = $this->pureClarityMapper->buildQuery($request, $pcResultTable->getName());
                
                $table = $temporaryStorage->storeDocumentsFromSelect($query);
                $documents = $this->getDocuments($table);
                $newDocuments = [];
                
                if (array_key_exists('personalizedProducts', $result)) {
                    $parsedPersonalProducts = [];
                    $personalProducts = $result['personalizedProducts'];
                    foreach ($personalProducts as $item) {
                        if (array_key_exists($item['Id'], $documents)) {
                            $parsedPersonalProducts[] = $item;
                        }
                    }
                    $result['personalizedProducts'] = $parsedPersonalProducts;
                    $this->service->updateSearchResult($result);
                }
            }
        } else {
            $query = $this->mapper->buildQuery($request);
            $table = $temporaryStorage->storeDocumentsFromSelect($query);
            $documents = $this->getDocuments($table);
        }
        
 
        $aggregations = $this->aggregationBuilder->build($request, $table);
 
        $response = [
            'documents' => $documents,
            'aggregations' => $aggregations,
        ];
        
        return $this->responseFactory->create($response);
    }

    private function getDocument20($document)
    {
        return new \Magento\Framework\Search\Document($document['entity_id'], ['score' => new \Magento\Framework\Search\DocumentField('score', $document['score'])]);
    }
    private function getDocument21($document)
    {
        return $this->documentFactory->create($document);
    }

    private function getDocuments(Table $table)
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from($table->getName(), ['entity_id', 'score']);
        return $connection->fetchAssoc($select);
    }

    private function getMagentoVersion()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        return $productMetadata->getVersion();
    }
 
    public function getConnection()
    {
        return $this->connectionManager->getConnection();
    }

    private function isSearchPage()
    {
        return $this->request->getFullActionName() == 'catalogsearch_result_index';
    }

    private function getCategoryId(RequestInterface $request)
    {
        if ($request &&
            $request->getQuery() &&
            $request->getQuery()->getMust() &&
            array_key_exists('category', $request->getQuery()->getMust()) &&
            $request->getQuery()->getMust()['category']->getReference() &&
            $request->getQuery()->getMust()['category']->getReference()->getValue()
            ) {
            return $request->getQuery()->getMust()['category']->getReference()->getValue();
        }
        return null;
    }

    private function isCategoryPage()
    {
        return ($this->request->getControllerName() == 'category');
    }
}
