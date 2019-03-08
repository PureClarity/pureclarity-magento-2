<?php
namespace Pureclarity\Core\Model;

class Feed extends \Magento\Framework\Model\AbstractModel
{

    protected $catalogResourceModelCategoryCollectionFactory;
    protected $categoryRepository;
    protected $coreHelper;
    protected $eavConfig;
    protected $storeFactory;
    protected $categoryHelper;
    protected $coreProductExportFactory;
    protected $logger;
    protected $customerGroup;
    protected $customerFactory;
    protected $orderFactory;
    protected $accessKey;
    protected $secretKey;
    protected $storeId;
    protected $progressFileName;
    protected $problemFeeds = [];

    private $uniqueId;
    private $currentStore;

    const FEED_TYPE_BRAND = "brand";
    const FEED_TYPE_CATEGORY = "category";
    const FEED_TYPE_PRODUCT = "product";
    const FEED_TYPE_ORDER = "orders";
    const FEED_TYPE_USER = "user";

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $catalogResourceModelCategoryCollectionFactory,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Pureclarity\Core\Model\ProductExportFactory $coreProductExportFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroup,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->catalogResourceModelCategoryCollectionFactory = $catalogResourceModelCategoryCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->coreHelper = $coreHelper;
        $this->eavConfig = $eavConfig;
        $this->storeFactory = $storeFactory;
        $this->categoryHelper = $categoryHelper;
        $this->coreProductExportFactory = $coreProductExportFactory;
        $this->logger = $context->getLogger();
        $this->customerFactory = $customerFactory;
        $this->customerGroup = $customerGroup;
        $this->orderFactory = $orderFactory;

        /*
         * If Magento does not have the recommended level of memory for PHP, can cause the feeds
         * to fail. If this happens, an appropriate message is logged.
         */
        register_shutdown_function("Pureclarity\Core\Model\Feed::logShutdown");

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Process the product feed and update the progress file, in page sizes
     * of 1 by default, speed gains for higher batches were negligible vs 
     * degrading progress feedback for user
     * @param $pageSize integer
     */
    function sendProducts($pageSize = 50)
    {

        if (! $this->isInitialised()) {
            return false;
        }

        $this->start(self::FEED_TYPE_PRODUCT);

        $this->logger->debug("PureClarity: In Feed->sendProducts()");
        $productExportModel = $this->coreProductExportFactory->create();
        $productExportModel->init($this->storeId);
        $this->logger->debug("PureClarity: Initialised ProductExport");

        $this->coreHelper->setProgressFile($this->progressFileName, self::FEED_TYPE_PRODUCT, 0, 1);
        $this->logger->debug("PureClarity: Set progress");
    
        $currentPage = 0;
        $pages = 0;
        $feedProducts = [];

        // loop through products, POSTing string for each page as it loops through
        $isFirst = true;
        do {
            $result = $productExportModel->getFullProductFeed($pageSize, $currentPage);

            $this->logger->debug("PureClarity: Got result from product export model");

            $pages = $result["Pages"];
        
            $json = ($isFirst ? ',"Products":[' : "");
            foreach ($result["Products"] as $product) {
                if (! $isFirst) {
                    $json .= ',';
                }
                $isFirst = false;
                $json .= $this->coreHelper->formatFeed($product, 'json');
            }
            $parameters = $this->getParameters($json, self::FEED_TYPE_PRODUCT);
            $this->send("feed-append", $parameters);

            $this->coreHelper->setProgressFile($this->progressFileName, self::FEED_TYPE_PRODUCT, $currentPage, $pages);
            $currentPage++;
        } while ($currentPage <= $pages);

        
        $hasSentItemData = (! $isFirst);
        $this->endFeedAppend(self::FEED_TYPE_PRODUCT, $hasSentItemData);

        $this->end(self::FEED_TYPE_PRODUCT);
        $this->logger->debug("PureClarity: Finished sending product data");
    }

    /**
     * Sends orders feed.
     */
    function sendOrders()
    {
        if (! $this->isInitialised()) {
            return false;
        }

        $this->start(self::FEED_TYPE_ORDER, true);

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
            // ->addAttributeToFilter('status', array('eq' => \Magento\Sales\Model\Order::STATE_COMPLETE));
        $this->logger->debug("PureClarity: Initialised orderCollection");

        // Set size and initiate vars
        $maxProgress = count($orderCollection);
        $currentProgress = 0;
        $counter = 0;
        $data = "";
        $isFirst = true;

        $this->logger->debug($maxProgress . " items");
        
        // Reset Progress file
        $this->coreHelper->setProgressFile($this->progressFileName, self::FEED_TYPE_ORDER, 0, 1);
        
        /**
         * \Magento\Framework\AppInterface::VERSION version constant was removed in 2.1+ so using
        * this to check if version 2.0
         */
        $isMagento20 = defined("\\Magento\\Framework\\AppInterface::VERSION");

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
                        $data .= "{$id},{$customerId},{$email},{$date},{$productId},{$quantity},{$price},{$linePrice}" . PHP_EOL;
                    }
                }
                $counter++;
            }

            // Increment counters
            $currentProgress++;

            if ($counter >= 10 || $maxProgress < 10) { // latter to ensure something comes through, if historic orders less than 10 we'll still get a feed
                // Every 10, send the data
                $parameters = $this->getParameters($data, self::FEED_TYPE_ORDER);
                $this->send("feed-append", $parameters);
                $data = "";
                $counter = 0;
                $this->coreHelper->setProgressFile($this->progressFileName, self::FEED_TYPE_ORDER, $currentProgress, $maxProgress);
            }
        }
        
        $hasSentItemData = (! $isFirst);
        $this->endFeedAppend(self::FEED_TYPE_ORDER, $hasSentItemData);

        $this->end(self::FEED_TYPE_ORDER, true);
        $this->logger->debug("PureClarity: Finished sending order data");
    }

    /**
     * Sends categories feed.
     */
    function sendCategories()
    {
        if (! $this->isInitialised()) {
            return false;
        }

        $this->start(self::FEED_TYPE_CATEGORY);
     
        $categoryCollection = $this->catalogResourceModelCategoryCollectionFactory->create()
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
        $isFirst = true;

        foreach ($categoryCollection as $category) {
            if (! $category->getName()) {
                continue;
            }

            $feedCategories = ($isFirst ? ',"Categories":[' : "");

            // Get category image
            $categoryImage = $category->getImageUrl();
            if ($categoryImage != "") {
                $categoryImageUrl = $categoryImage;
            } else {
                $categoryImageUrl = $this->coreHelper->getCategoryPlaceholderUrl($this->storeId);
            }
            $categoryImageUrl = $this->removeUrlProtocol($categoryImageUrl);

            
            // Get override image
            $overrideImageUrl = null;
            $overrideImage = $category->getData('pureclarity_category_image');
            if ($overrideImage != "") {
                $overrideImageUrl = sprintf("%scatalog/pureclarity_category_image/%s", $this->getCurrentStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA), $overrideImage);
            } else {
                $overrideImageUrl = $this->coreHelper->getSecondaryCategoryPlaceholderUrl($this->storeId);
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
            
            if (! $isFirst) {
                $feedCategories .= ',';
            }
            $isFirst = false;

            $feedCategories .= $this->coreHelper->formatFeed($categoryData, 'json');
            
            $currentProgress++;

            $parameters = $this->getParameters($feedCategories, self::FEED_TYPE_CATEGORY);
            $this->send("feed-append", $parameters);

            $this->coreHelper->setProgressFile($this->progressFileName, self::FEED_TYPE_CATEGORY, $currentProgress, $maxProgress);
        }
        
        $hasSentItemData = (! $isFirst);
        $this->endFeedAppend(self::FEED_TYPE_CATEGORY, $hasSentItemData);

        $this->end(self::FEED_TYPE_CATEGORY);
    }

    /**
     * Sends brands feed.
     */
    function sendBrands()
    {
        if (! $this->isInitialised()) {
            return false;
        }

        $this->start(self::FEED_TYPE_BRAND);

        $this->logger->debug("PureClarity: In Feed->sendBrands()");

        $feedBrands = [];
        $brandCategoryId = $this->coreHelper->getBrandParentCategory($this->storeId);
        
        if ($brandCategoryId && $brandCategoryId != "-1") {
            $brandParentCategory = $this->categoryRepository->get($brandCategoryId);
            
            $brands = $this->catalogResourceModelCategoryCollectionFactory->create()
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('image')
                ->addAttributeToSelect('pureclarity_category_image')
                ->addAttributeToSelect('pureclarity_hide_from_feed')
                ->addIdFilter($brandParentCategory ->getChildren());

            $maxProgress = count($brands);
            $feedBrands = "";
            $currentProgress = 0;
            $isFirst = true;

            foreach ($brands as $brand) {
                $feedBrands = ($isFirst ? ',"Brands":[' : "");

                $brandData = [
                    "Id" => $brand->getId(),
                    "DisplayName" =>  $brand->getName()
                ];

                // Get brand image
                $brandImage = $brand->getImageUrl();
                if ($brandImage != "") {
                    $brandImageUrl = $brandImage;
                } else {
                    $brandImageUrl = $this->coreHelper->getCategoryPlaceholderUrl($this->storeId);
                }
                $brandData['Image'] = $this->removeUrlProtocol($brandImageUrl);

                // Get override image
                $overrideImageUrl = null;
                $overrideImage = $brand->getData('pureclarity_category_image');
                if ($overrideImage != "") {
                    $overrideImageUrl = sprintf("%scatalog/pureclarity_category_image/%s", $this->getCurrentStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA), $overrideImage);
                } else {
                    $overrideImageUrl = $this->coreHelper->getSecondaryCategoryPlaceholderUrl($this->storeId);
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

                if (! $isFirst) {
                    $feedBrands .= ',';
                }
                $isFirst = false;
                $feedBrands .= $this->coreHelper->formatFeed($brandData, 'json');
                $currentProgress++;

                $parameters = $this->getParameters($feedBrands, self::FEED_TYPE_BRAND);
                $this->send("feed-append", $parameters);

                $this->coreHelper->setProgressFile($this->progressFileName, self::FEED_TYPE_BRAND, $currentProgress, $maxProgress);
            }
        
            $hasSentItemData = (! $isFirst);
            $this->endFeedAppend(self::FEED_TYPE_BRAND, $hasSentItemData);
        
        } else {
            $this->coreHelper->setProgressFile($this->progressFileName, self::FEED_TYPE_BRAND, 1, 1);
        }
        $this->end(self::FEED_TYPE_BRAND);
    }

    function BrandFeedArray($storeId)
    {

        $feedBrands = [];
        $brandCategoryId = $this->coreHelper->getBrandParentCategory($storeId);
        
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
     */
    function sendUsers()
    {

        if (! $this->isInitialised()) {
            return false;
        }

        $this->start(self::FEED_TYPE_USER);
        
        $this->logger->debug("PureClarity: In Feed->sendUsers()");
        $customerGroups = $this->customerGroup->toOptionArray();
        
        $users = "";
        $customerCollection = $this->customerFactory->create()->getCollection()
            ->addAttributeToFilter("website_id", [
                    "eq" => $this->getCurrentStore()->getWebsiteId()
                ])
            ->addAttributeToSelect("*")
            ->load();

        $maxProgress = count($customerCollection);
        $currentProgress = 0;
        $isFirst = true;

        foreach ($customerCollection as $customer) {
            $users = ($isFirst ? ',"Users":[' : "");

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

            $address = null;
            if ($customer->getDefaultShipping()) {
                $address = $customer->getAddresses()[$customer->getDefaultShipping()];
            } elseif ($customer->getAddresses() && sizeof(array_keys($customer->getAddresses())) > 0) {
                $address = $customer->getAddresses()[array_keys($customer->getAddresses())[0]];
            }
            if ($address) {
                if ($address->getCity()) {
                    $data['City'] = $address->getCity();
                }
                if ($address->getRegion()) {
                    $data['State'] = $address->getRegion();
                }
                if ($address->getCountry()) {
                    $data['Country'] = $address->getCountry();
                }
            }

            if (! $isFirst) {
                $users .= ',';
            }
            $isFirst = false;

            $users .= $this->coreHelper->formatFeed($data, 'json');
            
            $currentProgress += 1;

            $parameters = $this->getParameters($users, self::FEED_TYPE_USER);
            $this->send("feed-append", $parameters);

            $this->coreHelper->setProgressFile($this->progressFileName, self::FEED_TYPE_USER, $currentProgress, $maxProgress);
        }
        
        $hasSentItemData = (! $isFirst);
        $this->endFeedAppend(self::FEED_TYPE_USER, $hasSentItemData);

        $this->end(self::FEED_TYPE_USER);
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


    protected function endFeedAppend($feedType, $hasSentItemData){

        /*
         * Close the array if we've had at least one user
         */    
        if($hasSentItemData){
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
        
        $url = $this->coreHelper->getFeedBaseUrl($this->storeId) . $endPoint;
        
        $this->logger->debug("PureClarity: About to send data to {$url} for " . $parameters['feedName'] . ": " . print_r($parameters, true));

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
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->logger->debug('PureClarity: Error: ' . curl_error($ch));
            $feedTypeParts = explode("-", $parameters['feedName']);
            $feedType = $feedTypeParts[0];
            if(! in_array($feedType, $this->problemFeeds)){
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
        if(is_null($this->uniqueId)){
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
        $this->accessKey = $this->coreHelper->getAccessKey($this->storeId);
        $this->secretKey = $this->coreHelper->getSecretKey($this->storeId);
        if (empty($this->accessKey) || empty($this->secretKey)) {
            $this->coreHelper->setProgressFile($this->progressFileName, 'N/A', 1, 1, "false", "false", "", "Access Key and Secret Key must be set.");
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
                $this->logger->debug("PureClarity: No store id or progress file name, call initialise() on Model/Feed.php");
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
        } else {
            // Set to uploaded
            $this->coreHelper->setProgressFile($this->progressFileName, 'N/A', 1, 1, "true", "true");
        }
    }

    /**
     * If PHP has run out of memory to run the feeds, outputs an appropriate message to the logs. It's not possible to output to 
     * the GUI as e.g. the PHP process that monitors the progress file is also no longer responsive and just returns null.
     */
    public static function logShutdown()
    {
        $error = error_get_last();
        if ($error !== null && strpos($error['message'], 'Allowed memory size') !== false) {
            $errorMessage = "PureClarity: PHP does not have enough memory to run the feeds. Please increase to the recommended level of 768Mb and try again.";
            file_put_contents(BP . '/var/log/debug.log', $errorMessage, FILE_APPEND);
        }
    }

    private function getCurrentStore(){
        if(empty($this->currentStore)){
            $this->currentStore = $this->storeFactory->create()->load($this->storeId);
        }
        return $this->currentStore;
    }
}
