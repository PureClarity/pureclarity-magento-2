<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreFactory;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroupCollection;
use Pureclarity\Core\Helper\Service\Url;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\App\Area;

/**
 * Class Feed
 *
 * Handles running of feeds
 */
class Feed
{
    const FEED_TYPE_BRAND = "brand";
    const FEED_TYPE_CATEGORY = "category";
    const FEED_TYPE_PRODUCT = "product";
    const FEED_TYPE_ORDER = "orders";
    const FEED_TYPE_USER = "user";

    /** @var string[] $problemFeeds */
    private $problemFeeds = [];

    /** @var string $accessKey */
    private $accessKey;

    /** @var string $secretKey */
    private $secretKey;

    /** @var string $storeId */
    private $storeId;

    /** @var string $progressFileName */
    private $progressFileName;

    /** @var string $uniqueId */
    private $uniqueId;

    /** @var Store $currentStore */
    private $currentStore;

    /** @var CategoryCollectionFactory $categoryCollectionFactory */
    private $categoryCollectionFactory;

    /** @var CategoryRepository $categoryRepository */
    private $categoryRepository;

    /** @var Data $coreHelper */
    private $coreHelper;

    /** @var StoreFactory $storeFactory */
    private $storeFactory;

    /** @var ProductExportFactory $productExportFactory */
    private $productExportFactory;

    /** @var CustomerCollectionFactory $customerCollectionFactory */
    private $customerCollectionFactory;

    /** @var CustomerGroupCollection $customerGroupCollection */
    private $customerGroupCollection;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var Url $serviceUrl */
    private $serviceUrl;

    /** @var Emulation $appEmulation */
    private $appEmulation;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryRepository $categoryRepository
     * @param Data $coreHelper
     * @param StoreFactory $storeFactory
     * @param ProductExportFactory $productExportFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param CustomerGroupCollection $customerGroupCollection
     * @param StateRepositoryInterface $stateRepository
     * @param LoggerInterface $logger
     * @param CoreConfig $coreConfig
     * @param Url $serviceUrl
     * @param Emulation $appEmulation
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryRepository $categoryRepository,
        Data $coreHelper,
        StoreFactory $storeFactory,
        ProductExportFactory $productExportFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        CustomerGroupCollection $customerGroupCollection,
        StateRepositoryInterface $stateRepository,
        LoggerInterface $logger,
        CoreConfig $coreConfig,
        Url $serviceUrl,
        Emulation $appEmulation
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryRepository        = $categoryRepository;
        $this->coreHelper                = $coreHelper;
        $this->storeFactory              = $storeFactory;
        $this->productExportFactory      = $productExportFactory;
        $this->logger                    = $logger;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->customerGroupCollection   = $customerGroupCollection;
        $this->stateRepository           = $stateRepository;
        $this->coreConfig                = $coreConfig;
        $this->serviceUrl                = $serviceUrl;
        $this->appEmulation              = $appEmulation;

        /*
         * If Magento does not have the recommended level of memory for PHP, can cause the feeds
         * to fail. If this happens, an appropriate message is logged.
         */
        register_shutdown_function("Pureclarity\Core\Model\Feed::logShutdown");
    }

    /**
     * Process the product feed and update the progress file, in page sizes
     * of 50 by default, speed gains for higher batches were negligible vs
     * degrading progress feedback for user
     * @param $pageSize integer
     */
    public function sendProducts($pageSize = 50)
    {
        if (! $this->isInitialised()) {
            return false;
        }

        // emulate frontend so product images work correctly
        $this->appEmulation->startEnvironmentEmulation($this->storeId, Area::AREA_FRONTEND, true);

        try {
            $this->logger->debug("PureClarity: In Feed->sendProducts()");
            $productExportModel = $this->productExportFactory->create();
            $productExportModel->init($this->storeId);
            $this->logger->debug("PureClarity: Initialised ProductExport");

            $this->coreHelper->setProgressFile($this->progressFileName, self::FEED_TYPE_PRODUCT, 0, 1);
            $this->logger->debug("PureClarity: Set progress");

            $currentPage = 0;
            $pages = 0;

            // loop through products, POSTing string for each page as it loops through
            $writtenProduct = false;
            do {
                $result = $productExportModel->getFullProductFeed($pageSize, $currentPage);

                if (!empty($result["Products"])) {
                    if (!$writtenProduct) {
                        $this->start(self::FEED_TYPE_PRODUCT);
                    }

                    $this->logger->debug("PureClarity: Got result from product export model");

                    $pages = $result["Pages"];

                    $json = (!$writtenProduct ? ',"Products":[' : "");
                    foreach ($result["Products"] as $product) {
                        if ($writtenProduct) {
                            $json .= ',';
                        }
                        $writtenProduct = true;
                        $json .= $this->coreHelper->formatFeed($product, 'json');
                    }

                    $parameters = $this->getParameters($json, self::FEED_TYPE_PRODUCT);

                    if ($writtenProduct) {
                        $this->send("feed-append", $parameters);
                    }

                    $this->coreHelper->setProgressFile(
                        $this->progressFileName,
                        self::FEED_TYPE_PRODUCT,
                        $currentPage,
                        $pages
                    );
                }
                $currentPage++;
            } while ($currentPage <= $pages);

            $this->endFeedAppend(self::FEED_TYPE_PRODUCT, $writtenProduct);

            if ($writtenProduct) {
                $this->end(self::FEED_TYPE_PRODUCT);
                $this->saveRunDate(self::FEED_TYPE_PRODUCT, $this->storeId);
            } else {
                $this->logger->debug("PureClarity: Could not find any product to upload");
            }

            $this->logger->debug("PureClarity: Finished sending product data");
        } catch (\Exception $e) {
            $this->logger->debug("PureClarity: Product feed error: " . $e->getMessage());
        }

        $this->appEmulation->stopEnvironmentEmulation();
    }

    /**
     * Sends orders feed.
     */
    public function sendOrders()
    {
        if (! $this->isInitialised()) {
            return false;
        }

        $this->logger->debug("PureClarity: In Feed->sendOrders()");
        
        // Get the collection
        $fromDate = date('Y-m-d H:i:s', strtotime("-12 month"));
        $toDate = date('Y-m-d H:i:s', strtotime("now"));
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $this->logger->debug("PureClarity: About to initialise orderCollection");
        $orderCollection = $objectManager->get('Magento\Sales\Model\Order')
            ->getCollection()
            ->addAttributeToFilter('store_id', $this->storeId)
            ->addAttributeToFilter('created_at', ['from'=>$fromDate, 'to'=>$toDate]);
        $this->logger->debug("PureClarity: Initialised orderCollection");

        // Set size and initiate vars
        $maxProgress = count($orderCollection);
        $currentProgress = 0;
        $counter = 0;
        $data = "";

        $this->logger->debug($maxProgress . " items");
        
        /**
         * \Magento\Framework\AppInterface::VERSION version constant was removed in 2.1+ so using
        * this to check if version 2.0
         */
        $isMagento20 = defined("\\Magento\\Framework\\AppInterface::VERSION");

        if ($maxProgress > 0) {
            $this->start(self::FEED_TYPE_ORDER, true);
            // Reset Progress file
            $this->coreHelper->setProgressFile($this->progressFileName, self::FEED_TYPE_ORDER, 0, 1);
            
            // Build Data
            foreach ($orderCollection as $orderData) {
                $order = $objectManager->create('Magento\Sales\Model\Order')
                    ->loadByIncrementId($orderData->getIncrementId());
                if ($order) {
                    $id = $order->getIncrementId();
                    $this->logger->debug("Order id {$id}");
                    $customerId = $order->getCustomerId();
                    $email = $order->getCustomerEmail();
                    $date = $order->getCreatedAt();
                    
                    $orderItems = $orderData->getAllVisibleItems();
                    foreach ($orderItems as $item) {
                        $productId = $item->getProductId();
                        $quantity = $item->getQtyOrdered();
                        $price = ($isMagento20 ? 0.00 : $item->getPriceInclTax());
                        $this->logger->debug("Price {$price}");
                        $linePrice = ($isMagento20 ? 0.00 : $item->getRowTotalInclTax());
                        $this->logger->debug("Line price {$linePrice}");

                        /**
                         * On Magento 2.0, $price and $linePrice are null, functions exist but don't appear to work.
                         * Therefore for 2.0, add data anyway without pricing check; otherwise do pricing check.
                         * Need to set to 0.00 above for 2.0, otherwise invalid pricing format and not accepted
                         * into PureClarity.
                         */
                        if ($isMagento20
                            || ($price > 0 && $linePrice > 0)) {
                            $data .= "{$id},{$customerId},{$email},{$date},"
                                  .  "{$productId},{$quantity},{$price},{$linePrice}" . PHP_EOL;
                        }
                    }
                    $counter++;
                }

                // Increment counters
                $currentProgress++;

                // latter to ensure something comes through, if historic orders less than 10 we'll still get a feed
                if ($counter >= 10 || $maxProgress < 10) {
                    // Every 10, send the data
                    $parameters = $this->getParameters($data, self::FEED_TYPE_ORDER);
                    $this->send("feed-append", $parameters);
                    $data = "";
                    $counter = 0;
                    $this->coreHelper->setProgressFile(
                        $this->progressFileName,
                        self::FEED_TYPE_ORDER,
                        $currentProgress,
                        $maxProgress
                    );
                }
            }
            
            // send any left-over data
            if ($counter > 0) {
                $parameters = $this->getParameters($data, self::FEED_TYPE_ORDER);
                $this->send("feed-append", $parameters);
                $this->coreHelper->setProgressFile(
                    $this->progressFileName,
                    self::FEED_TYPE_ORDER,
                    $currentProgress,
                    $maxProgress
                );
            }
            
            $this->end(self::FEED_TYPE_ORDER, true);
            $this->logger->debug("PureClarity: Finished sending order data");
            $this->saveRunDate(self::FEED_TYPE_ORDER, $this->storeId);
        }
    }

    /**
     * Sends categories feed.
     */
    public function sendCategories()
    {
        if (! $this->isInitialised()) {
            return false;
        }

        $categoryCollection = $this->categoryCollectionFactory->create()
            ->setStore($this->getCurrentStore())
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('pureclarity_category_image')
            ->addAttributeToSelect('pureclarity_hide_from_feed')
            ->addUrlRewriteToResult();
        $this->coreHelper->setProgressFile($this->progressFileName, self::FEED_TYPE_CATEGORY, 0, 1);

        $maxProgress = count($categoryCollection);
        $currentProgress = 0;
        $writtenCategories = false;
        
        if ($maxProgress > 0) {
            foreach ($categoryCollection as $category) {
                if (! $category->getName()) {
                    continue;
                }

                $feedCategories = (!$writtenCategories ? ',"Categories":[' : "");

                // Get category image
                $categoryImage = $category->getImageUrl();
                if ($categoryImage != "") {
                    $categoryImageUrl = $categoryImage;
                } else {
                    $categoryImageUrl = $this->coreConfig->getCategoryPlaceholderUrl($this->storeId);
                }
                $categoryImageUrl = $this->removeUrlProtocol($categoryImageUrl);
                
                // Get override image
                $overrideImageUrl = null;
                $overrideImage = $category->getData('pureclarity_category_image');
                if ($overrideImage != "") {
                    $overrideImageUrl = sprintf(
                        "%scatalog/pureclarity_category_image/%s",
                        $this->getCurrentStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA),
                        $overrideImage
                    );
                } else {
                    $overrideImageUrl = $this->coreConfig->getSecondaryCategoryPlaceholderUrl($this->storeId);
                }
                $overrideImageUrl = $this->removeUrlProtocol($overrideImageUrl);

                // Build data
                $categoryData = [
                    "Id" => $category->getId(),
                    "DisplayName" => $category->getName(),
                    "Image" => $categoryImageUrl,
                    "Link" => "/"
                ];

                // Set URL and Parent ID
                if ($category->getLevel() > 1) {
                    $categoryData["Link"] = $this->removeUrlProtocol($category->getUrl($category));
                    $categoryData["ParentIds"] = [
                            $category->getParentCategory()->getId()
                        ];
                }
                
                // Check whether to ignore this category in recommenders
                if ($category->getData('pureclarity_hide_from_feed') == '1') {
                    $categoryData["ExcludeFromRecommenders"] = true;
                }

                //Check if category is active
                if (!$category->getIsActive()) {
                    $categoryData["IsActive"] = false;
                }

                if ($overrideImageUrl != null) {
                    $categoryData["OverrideImage"] = $overrideImageUrl;
                }
                
                if ($writtenCategories) {
                    $feedCategories .= ',';
                }
                
                $feedCategories .= $this->coreHelper->formatFeed($categoryData, 'json');
                
                $currentProgress++;

                $parameters = $this->getParameters($feedCategories, self::FEED_TYPE_CATEGORY);
                
                if (!$writtenCategories) {
                    $this->start(self::FEED_TYPE_CATEGORY);
                }
                
                $this->send("feed-append", $parameters);

                $this->coreHelper->setProgressFile(
                    $this->progressFileName,
                    self::FEED_TYPE_CATEGORY,
                    $currentProgress,
                    $maxProgress
                );
                
                $writtenCategories = true;
            }
            
            $this->endFeedAppend(self::FEED_TYPE_CATEGORY, $writtenCategories);

            if ($writtenCategories) {
                $this->end(self::FEED_TYPE_CATEGORY);
                $this->saveRunDate(self::FEED_TYPE_CATEGORY, $this->storeId);
            }
        }
    }

    /**
     * Sends brands feed.
     */
    public function sendBrands()
    {
        if (! $this->isInitialised()) {
            return false;
        }

        $this->logger->debug("PureClarity: In Feed->sendBrands()");

        $feedBrands = [];
        $brandCategoryId = $this->coreConfig->getBrandParentCategory($this->storeId);
        
        if ($brandCategoryId && $brandCategoryId != "-1") {
            $brandParentCategory = $this->categoryRepository->get($brandCategoryId);
            
            $brands = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('image')
                ->addAttributeToSelect('pureclarity_category_image')
                ->addAttributeToSelect('pureclarity_hide_from_feed')
                ->addIdFilter($brandParentCategory ->getChildren());

            $maxProgress = count($brands);
            $feedBrands = "";
            $currentProgress = 0;
            $writtenBrands = false;
            if ($maxProgress > 0) {
                $this->start(self::FEED_TYPE_BRAND);

                foreach ($brands as $brand) {
                    $feedBrands = (!$writtenBrands ? ',"Brands":[' : "");

                    $brandData = [
                        "Id" => $brand->getId(),
                        "DisplayName" =>  $brand->getName()
                    ];

                    // Get brand image
                    $brandImage = $brand->getImageUrl();
                    if ($brandImage != "") {
                        $brandImageUrl = $brandImage;
                    } else {
                        $brandImageUrl = $this->coreConfig->getCategoryPlaceholderUrl($this->storeId);
                    }
                    $brandData['Image'] = $this->removeUrlProtocol($brandImageUrl);

                    // Get override image
                    $overrideImageUrl = null;
                    $overrideImage = $brand->getData('pureclarity_category_image');
                    if ($overrideImage != "") {
                        $overrideImageUrl = sprintf(
                            "%scatalog/pureclarity_category_image/%s",
                            $this->getCurrentStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA),
                            $overrideImage
                        );
                    } else {
                        $overrideImageUrl = $this->coreConfig->getSecondaryCategoryPlaceholderUrl($this->storeId);
                    }
                    $overrideImageUrl = $this->removeUrlProtocol($overrideImageUrl);
                    if ($overrideImageUrl != null) {
                        $brandData["OverrideImage"] = $overrideImageUrl;
                    }

                    $brandData["Link"] = $this->removeUrlProtocol($brand->getUrl($brand));

                    // Check whether to ignore this brand in recommenders
                    if ($brand->getData('pureclarity_hide_from_feed') == '1') {
                        $brandData["ExcludeFromRecommenders"] = true;
                    }

                    if ($writtenBrands) {
                        $feedBrands .= ',';
                    }
                    $writtenBrands = true;
                    $feedBrands .= $this->coreHelper->formatFeed($brandData, 'json');
                    $currentProgress++;

                    $parameters = $this->getParameters($feedBrands, self::FEED_TYPE_BRAND);
                    $this->send("feed-append", $parameters);

                    $this->coreHelper->setProgressFile(
                        $this->progressFileName,
                        self::FEED_TYPE_BRAND,
                        $currentProgress,
                        $maxProgress
                    );
                }
        
                $this->endFeedAppend(self::FEED_TYPE_BRAND, $writtenBrands);
                $this->end(self::FEED_TYPE_BRAND);
                $this->saveRunDate(self::FEED_TYPE_BRAND, $this->storeId);
            }
        } else {
            $this->coreHelper->setProgressFile($this->progressFileName, self::FEED_TYPE_BRAND, 1, 1);
        }
    }

    public function BrandFeedArray($storeId)
    {

        $feedBrands = [];
        $brandCategoryId = $this->coreConfig->getBrandParentCategory($storeId);
        
        if ($brandCategoryId && $brandCategoryId != "-1") {
            $category = $this->categoryRepository->get($brandCategoryId);
            $subcategories = $category->getChildrenCategories();
            $maxProgress = count($subcategories);
            $currentProgress = 0;
            $isFirst = true;
            foreach ($subcategories as $subcategory) {
                $feedBrands[$subcategory->getId()] = $subcategory->getName();
            }
            return $feedBrands;
        }
        return [];
    }

    /**
     * Sends users feed
     * @return boolean
     */
    public function sendUsers()
    {
        if (!$this->isInitialised()) {
            return false;
        }

        $this->logger->debug("PureClarity: In Feed->sendUsers()");
        $customerGroups = $this->customerGroupCollection->toOptionArray();

        $customerCollection = $this->getCustomerCollection();

        if (!$customerCollection) {
            return false;
        }

        $maxProgress = $customerCollection->getSize();

        $currentProgress = 0;
        $writtenCustomers = false;
        $this->logger->debug("PureClarity: {$maxProgress} users");
        if ($maxProgress > 0) {
            $this->start(self::FEED_TYPE_USER);

            $parameters = $this->getParameters(',"Users":[', self::FEED_TYPE_USER);
            $this->send("feed-append", $parameters);
            $users = '';

            /** @var \Magento\Customer\Model\Customer $customer */
            foreach ($customerCollection as $customer) {
                $data = [
                    'UserId' => $customer->getId(),
                    'Email' => $customer->getEmail(),
                    'FirstName' => $customer->getFirstname(),
                    'LastName' => $customer->getLastname()
                ];
                if ($customer->getPrefix()) {
                    $data['Salutation'] = $customer->getPrefix();
                }
                if ($customer->getDob()) {
                    $data['DOB'] = $customer->getDob();
                }
                if ($customer->getGroupId() && $customerGroups[$customer->getGroupId()]) {
                    $data['Group'] = $customerGroups[$customer->getGroupId()]['label'];
                    $data['GroupId'] = $customer->getGroupId();
                }
                if ($customer->getGender()) {
                    switch ($customer->getGender()) {
                        case 1: // Male
                            $data['Gender'] = 'M';
                            break;
                        case 2: // Female
                            $data['Gender'] = 'F';
                            break;
                    }
                }

                $data['City'] = $customer->getData('city');
                $data['State'] = $customer->getData('region');
                $data['Country'] = $customer->getData('country_id');

                if ($writtenCustomers) {
                    $users .= ',';
                }
                $writtenCustomers = true;

                $users .= $this->coreHelper->formatFeed($data, 'json');
                
                $currentProgress++;

                if ($currentProgress %100 === 0 || $currentProgress === $maxProgress) {
                    $parameters = $this->getParameters($users, self::FEED_TYPE_USER);
                    $this->send("feed-append", $parameters);
                    $users = '';
                }

                $this->coreHelper->setProgressFile(
                    $this->progressFileName,
                    self::FEED_TYPE_USER,
                    $currentProgress,
                    $maxProgress
                );
            }
            
            $this->endFeedAppend(self::FEED_TYPE_USER, $writtenCustomers);
            $this->end(self::FEED_TYPE_USER);
            $this->saveRunDate(self::FEED_TYPE_USER, $this->storeId);
        }

        return true;
    }

    /**
     * Builds the customer collection for user feed, includes default shipping / first address found
     * @return bool|\Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    private function getCustomerCollection()
    {
        try {
            $customerCollection = $this->customerCollectionFactory->create();
            $customerCollection->addAttributeToFilter(
                'website_id',
                [ "eq" => $this->getCurrentStore()->getWebsiteId()]
            );

            $table = $customerCollection->getTable('customer_address_entity');
            $customerCollection->joinTable(
                ['cad' => $table],
                'parent_id = entity_id',
                ['city', 'region', 'country_id'],
                '`cad`.entity_id=`e`.default_shipping OR cad.parent_id = e.entity_id',
                'left'
            );
            $customerCollection->groupByAttribute('entity_id');
            return $customerCollection;
        } catch (LocalizedException $e) {
            $this->logger->error('PureClarity, could not load users: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Removes protocol from the start of $url
     * @param $url string
     */
    protected function removeUrlProtocol($url)
    {
        return str_replace([
                "https:",
                "http:"
            ], "", $url);
    }

    /**
     * Starts the feed by sending first bit of data to feed-create end point. For orders,
     * sends first row of CSV data, otherwise sends opening string of json.
     * @param $feedType string One of the Feed::FEED_TYPE_... constants
     */
    protected function start($feedType)
    {
        if ($feedType == self::FEED_TYPE_ORDER) {
            $startJson = "OrderId,UserId,Email,DateTimeStamp,ProdCode,Quantity,UnityPrice,LinePrice" . PHP_EOL;
        } else {
            $startJson = '{"Version": 2';
        }
        $parameters = $this->getParameters($startJson, $feedType);
        $this->send("feed-create", $parameters);
        $this->logger->debug("PureClarity: Started feed");
    }

    /**
     * End the feed by sending any closing data to the feed-close end point. For order feeds,
     * no closing data is sent, the end point is simply called. For others, it's simply a closing
     * bracket.
     * @param $feedType string One of the Feed::FEED_TYPE_... constants
     */
    protected function end($feedType)
    {
        $data = ( $feedType == self::FEED_TYPE_ORDER ? '' : '}' );
        $this->send("feed-close", $this->getParameters($data, $feedType));
        // Ensure progress file is set to complete
        $this->coreHelper->setProgressFile($this->progressFileName, 'N/A', 1, 1, "true", "false");
    }

    protected function endFeedAppend($feedType, $hasSentItemData)
    {

        /*
         * Close the array if we've had at least one user
         */
        if ($hasSentItemData) {
            $parameters = $this->getParameters(']', $feedType);
            $this->send("feed-append", $parameters);
        }
    }

    /**
     * Sends the data to the specified end point, i.e. sends feed to PureClarity
     * @param $endPoint string
     * @param $parameters array
     */
    protected function send($endPoint, $parameters)
    {
        $url = $this->serviceUrl->getFeedSftpUrl($this->coreConfig->getRegion($this->storeId)) . $endPoint;

        $this->logger->debug(
            "PureClarity: About to send data to {$url} for " . $parameters['feedName']
            . ": " . print_r($parameters, true)
        );
        
        $post_fields = http_build_query($parameters);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 5000);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 10000);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        if (! empty($post_fields)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Content-Length: ' . strlen($post_fields)
                ]);
        } else {
            curl_setopt($ch, CURLOPT_POST, false);
        }

        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->logger->debug('PureClarity: Error: ' . curl_error($ch));
            $feedTypeParts = explode("-", $parameters['feedName']);
            $feedType = $feedTypeParts[0];
            if (! in_array($feedType, $this->problemFeeds)) {
                $this->problemFeeds[] = $feedType;
            }
        }

        curl_close($ch);
    
        $this->logger->debug("PureClarity: Response: " . print_r($response, true));
        $this->logger->debug("PureClarity: At end of send");
    }

    /**
     * Returns parameters ready for POSTing.
     * @param $data string
     * @param $feedType string One of Feed::FEED_TYPE... constants
     */
    protected function getParameters($data, $feedType)
    {
        if (! $this->isInitialised()) {
            return false;
        }
        $parameters = [
            "accessKey" => $this->accessKey,
            "secretKey" => $this->secretKey,
            "feedName" => $feedType . "-" . $this->getUniqueId()
        ];
        if (! empty($data)) {
            $parameters["payLoad"] = $data;
        }
        return $parameters;
    }

    private function getUniqueId()
    {
        if (is_null($this->uniqueId)) {
            $this->uniqueId = uniqid();
        }
        return $this->uniqueId;
    }

    /**
     * Initialises Feed object with store id and name of the progress file. Call after
     * creating via factory.
     * @param $storeId integer
     * @param $progressFileName string
     */
    public function initialise($storeId, $progressFileName)
    {
        $this->storeId = $storeId;
        $this->progressFileName = $progressFileName;
        $this->accessKey = $this->coreConfig->getAccessKey($this->storeId);
        $this->secretKey = $this->coreConfig->getSecretKey($this->storeId);

        if (empty($this->accessKey) || empty($this->secretKey)) {
            $this->coreHelper->setProgressFile(
                $this->progressFileName,
                'N/A',
                1,
                1,
                "false",
                "false",
                "",
                "Access Key and Secret Key must be set."
            );
            return false;
        }
        return $this;
    }

    /**
     * Returns true if Feed object has been correctly initialised. storeId and progressFileName
     * needs to be set on instantiation, access and secret keys need to be set in Magento.
     * @return boolean
     */
    protected function isInitialised()
    {
        if (empty($this->accessKey)
            || empty($this->secretKey)
            || empty($this->storeId)
            || empty($this->progressFileName)
            ) {
            if (empty($this->accessKey)
                    || empty($this->secretKey)) {
                $this->logger->debug("PureClarity: No access key or secret key, call initialise() on Model/Feed.php");
            }
            if (empty($this->storeId)
                    || empty($this->progressFileName)) {
                $this->logger->debug(
                    "PureClarity: No store id or progress file name, call initialise() on Model/Feed.php"
                );
            }
                return false;
        } else {
            return true;
        }
    }

    /**
     * Checks whether the POSTing of feeds has been successful and displays
     * appropriate message
     */
    public function checkSuccess()
    {
        $problemFeedCount = count($this->problemFeeds);
        if ($problemFeedCount) {
            $errorMessage = "There was a problem uploading the ";
            $counter = 1;
            foreach ($this->problemFeeds as $problemFeed) {
                $errorMessage .= $problemFeed;
                if ($counter < ($problemFeedCount - 1) && $problemFeedCount !== 2) {
                    $errorMessage .= ", ";
                } elseif ($problemFeedCount >= 2 && $counter == ($problemFeedCount - 1)) {
                    $errorMessage .= " and ";
                }
                $counter++;
            }
            $errorMessage .= " feed" . ($problemFeedCount > 1 ? "s" : "");
            $errorMessage .= ". Please see error logs for more information.";
            $this->coreHelper->setProgressFile($this->progressFileName, 'N/A', 1, 1, "true", "false", $errorMessage);
            $this->saveFeedError(implode(',', $this->problemFeeds), $this->storeId);
        } else {
            // Set to uploaded
            $this->coreHelper->setProgressFile($this->progressFileName, 'N/A', 1, 1, "true", "true");
            $this->saveFeedError('', $this->storeId);
        }
    }

    /**
     * If PHP has run out of memory to run the feeds, outputs an appropriate message to the logs.
     * It's not possible to output to the GUI as e.g. the PHP process that monitors the progress
     * file is also no longer responsive and just returns null.
     */
    public static function logShutdown()
    {
        $error = error_get_last();
        if ($error !== null && strpos($error['message'], 'Allowed memory size') !== false) {
            $errorMessage = "PureClarity: PHP does not have enough memory to run the feeds. "
                          . "Please increase to the recommended level of 768Mb and try again.";
            file_put_contents(BP . '/var/log/debug.log', $errorMessage, FILE_APPEND);
        }
    }

    private function getCurrentStore()
    {
        if (empty($this->currentStore)) {
            $this->currentStore = $this->storeFactory->create()->load($this->storeId);
        }
        return $this->currentStore;
    }

    /**
     * Saves the last run date of the provided feed
     * @param string $feedType
     * @param integer $storeId
     * @return void
     */
    private function saveRunDate($feedType, $storeId)
    {
        $state = $this->stateRepository->getByNameAndStore('last_' . $feedType . '_feed_date', $storeId);
        $state->setName('last_' . $feedType . '_feed_date');
        $state->setValue(date('Y-m-d H:i:s'));
        $state->setStoreId($storeId);

        try {
            $this->stateRepository->save($state);
        } catch (CouldNotSaveException $e) {
            $this->logger->error('Could not save last updated date: ' . $e->getMessage());
        }
    }

    /**
     * Saves the feed error status for the given store
     * @param string $feedTypes
     * @param integer $storeId
     * @return void
     */
    private function saveFeedError($feedTypes, $storeId)
    {
        $state = $this->stateRepository->getByNameAndStore('last_feed_error', $storeId);
        $state->setName('last_feed_error');
        $state->setValue($feedTypes);
        $state->setStoreId($storeId);

        try {
            $this->stateRepository->save($state);
        } catch (CouldNotSaveException $e) {
            $this->logger->error('Could not save last feed error: ' . $e->getMessage());
        }
    }
}
