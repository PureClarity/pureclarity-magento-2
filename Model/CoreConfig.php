<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class CoreConfig
 *
 * Class for getting all PureClarity Config values and setting some of them used by the signup process
 */
class CoreConfig
{
    const CONFIG_PATH_ACTIVE = 'pureclarity/environment/active';
    const CONFIG_PATH_ACCESS_KEY = 'pureclarity/credentials/access_key';
    const CONFIG_PATH_SECRET_KEY = 'pureclarity/credentials/secret_key';
    const CONFIG_PATH_MODE = 'pureclarity/mode/mode';
    const CONFIG_PATH_REGION = 'pureclarity/credentials/region';
    const CONFIG_PATH_PRODUCT_INDEX = 'pureclarity/feeds/product_index';
    const CONFIG_PATH_CUSTOMER_GROUP_PRICING = 'pureclarity/feeds/product_send_customer_group_pricing';
    const CONFIG_PATH_DAILY_FEED_ENABLED = 'pureclarity/feeds/notify_feed';
    const CONFIG_PATH_BRAND_FEED_ENABLED = 'pureclarity/feeds/brand_feed_enabled';
    const CONFIG_PATH_BRAND_CATEGORY = 'pureclarity/feeds/brand_parent_category';
    const CONFIG_PATH_EXCLUDED_PRODUCT_ATTRIBUTES = 'pureclarity/feeds/excluded_product_attributes';
    const CONFIG_PATH_EXCLUDE_OOS_PRODUCTS_RECS = 'pureclarity/feeds/exclude_out_of_stock_products_from_recs';
    const CONFIG_PATH_PLACEHOLDER_PRODUCT = 'pureclarity/placeholders/placeholder_product';
    const CONFIG_PATH_PLACEHOLDER_CATEGORY = 'pureclarity/placeholders/placeholder_category';
    const CONFIG_PATH_PLACEHOLDER_CATEGORY_SECONDARY = 'pureclarity/placeholders/placeholder_category_secondary';
    const CONFIG_PATH_ZONE_DEBUG = 'pureclarity/advanced/bmz_debug';
    const CONFIG_PATH_SWATCHES_PER_PRODUCT = 'catalog/frontend/swatches_per_product';
    const CONFIG_PATH_SWATCHES_IN_PRODUCT_LIST = 'catalog/frontend/show_swatches_in_product_list';
    const CONFIG_PATH_DEBUG_LOGGING = 'pureclarity/logging/debug_logging_enabled';

    /** @var ScopeConfigInterface $scopeConfig */
    protected $scopeConfig;

    /** @var WriterInterface $configWriter */
    protected $configWriter;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter
    ) {
        $this->scopeConfig  = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    /**
     * Returns config for whether PureClarity is Active or not on the given store
     *
     * @param integer $storeId
     * @return boolean
     */
    public function isActive($storeId)
    {
        $accessKey = $this->getAccessKey($storeId);
        if ($accessKey != null && $accessKey != "") {
            return $this->getConfigFlag(
                self::CONFIG_PATH_ACTIVE,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return false;
    }

    /**
     * Returns configured Access Key for the given store
     *
     * @param integer $storeId
     * @return string
     */
    public function getAccessKey($storeId)
    {
        return $this->getConfigValue(
            self::CONFIG_PATH_ACCESS_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns configured Secret Key for the given store
     *
     * @param integer $storeId
     * @return string
     */
    public function getSecretKey($storeId)
    {
        return $this->getConfigValue(
            self::CONFIG_PATH_SECRET_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns configured Region for the given store
     *
     * All infrastructure is consolidated to eu-west-1 (region 1).
     *
     * @param integer $storeId
     * @return integer
     */
    public function getRegion($storeId)
    {
        return 1;
    }

    /**
     * Returns configured mode
     *
     * @param integer $storeId
     * @return string
     */
    public function getMode($storeId)
    {
        return $this->getConfigValue(
            self::CONFIG_PATH_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Returns whether the Daily Feed is active on the given store
     *
     * @param integer $storeId
     * @return boolean
     */
    public function isDailyFeedActive($storeId)
    {
        if ($this->isActive($storeId)) {
            return $this->getConfigFlag(
                self::CONFIG_PATH_DAILY_FEED_ENABLED,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return false;
    }

    /**
     * Returns whether PureClarity Product Indexing (Deltas) are enabled on the given store
     *
     * @param integer $storeId
     * @return boolean
     */
    public function areDeltasEnabled($storeId)
    {
        if ($this->isActive($storeId)) {
            return $this->getConfigFlag(
                self::CONFIG_PATH_PRODUCT_INDEX,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return false;
    }

    /**
     * Returns whether customer group pricing should be sent in the product feed
     *
     * @param integer $storeId
     * @return boolean
     */
    public function sendCustomerGroupPricing($storeId)
    {
        if ($this->isActive($storeId)) {
            return $this->getConfigFlag(
                self::CONFIG_PATH_CUSTOMER_GROUP_PRICING,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return false;
    }

    /**
     * Returns whether Brand Feeds are enabled on the given store
     *
     * @param integer $storeId
     * @return boolean
     */
    public function isBrandFeedEnabled($storeId)
    {
        if ($this->isActive($storeId)) {
            return $this->getConfigFlag(
                self::CONFIG_PATH_BRAND_FEED_ENABLED,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return false;
    }

    /**
     * Returns the configured Brand Category for the given store
     *
     * @param integer $storeId
     * @return string
     */
    public function getBrandParentCategory($storeId)
    {
        return $this->getConfigValue(
            self::CONFIG_PATH_BRAND_CATEGORY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the attributes to be excluded from the feed, for the given store
     *
     * @param integer $storeId
     * @return string
     */
    public function getExcludedProductAttributes($storeId)
    {
        return $this->getConfigValue(
            self::CONFIG_PATH_EXCLUDED_PRODUCT_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns whether to exclude out of stock products from recommenders
     *
     * @param integer $storeId
     * @return string
     */
    public function getExcludeOutOfStockFromRecommenders($storeId)
    {
        return $this->getConfigFlag(
            self::CONFIG_PATH_EXCLUDE_OOS_PRODUCTS_RECS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the configured Product Image Placeholder URL for the given store
     *
     * @param integer $storeId
     * @return string
     */
    public function getProductPlaceholderUrl($storeId)
    {
        return $this->getConfigValue(
            self::CONFIG_PATH_PLACEHOLDER_PRODUCT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the configured Category Image Placeholder URL for the given store
     *
     * @param integer $storeId
     * @return string
     */
    public function getCategoryPlaceholderUrl($storeId)
    {
        return $this->getConfigValue(
            self::CONFIG_PATH_PLACEHOLDER_CATEGORY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the configured Secondary Category Image Placeholder URL for the given store
     *
     * @param integer $storeId
     * @return string
     */
    public function getSecondaryCategoryPlaceholderUrl($storeId)
    {
        return $this->getConfigValue(
            self::CONFIG_PATH_PLACEHOLDER_CATEGORY_SECONDARY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns whether debug mode is enabled for Zones
     *
     * @param integer $storeId
     * @return boolean
     */
    public function isZoneDebugActive($storeId)
    {
        return $this->getConfigFlag(
            self::CONFIG_PATH_ZONE_DEBUG,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the configured number of swatches per product for the given store
     *
     * @param integer $storeId
     * @return string
     */
    public function getNumberSwatchesPerProduct($storeId)
    {
        return $this->getConfigValue(
            self::CONFIG_PATH_SWATCHES_PER_PRODUCT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns whether products should show swatches
     *
     * @param integer $storeId
     * @return boolean
     */
    public function showSwatches($storeId)
    {
        return $this->getConfigFlag(
            self::CONFIG_PATH_SWATCHES_IN_PRODUCT_LIST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns whether debug logging is enabled
     *
     * @return boolean
     */
    public function isDebugLoggingEnabled(): bool
    {
        return $this->getConfigFlag(
            self::CONFIG_PATH_DEBUG_LOGGING,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Saves the Access Key config
     *
     * @param string $value
     * @param integer $storeId
     *
     * @return void
     */
    public function setAccessKey($value, $storeId)
    {
        $this->setConfigValue(
            self::CONFIG_PATH_ACCESS_KEY,
            $value,
            $storeId
        );
    }

    /**
     * Saves the Secret Key config
     *
     * @param string $value
     * @param integer $storeId
     *
     * @return void
     */
    public function setSecretKey($value, $storeId)
    {
        $this->setConfigValue(
            self::CONFIG_PATH_SECRET_KEY,
            $value,
            $storeId
        );
    }

    /**
     * Saves the Region config
     *
     * @param string $value
     * @param integer $storeId
     *
     * @return void
     */
    public function setRegion($value, $storeId)
    {
        $this->setConfigValue(
            self::CONFIG_PATH_REGION,
            $value,
            $storeId
        );
    }

    /**
     * Saves the active flag config
     *
     * @param string $value
     * @param integer $storeId
     *
     * @return void
     */
    public function setIsActive($value, $storeId)
    {
        $this->setConfigValue(
            self::CONFIG_PATH_ACTIVE,
            $value,
            $storeId
        );
    }

    /**
     * Saves the daily feed config
     *
     * @param string $value
     * @param integer $storeId
     *
     * @return void
     */
    public function setIsDailyFeedActive($value, $storeId)
    {
        $this->setConfigValue(
            self::CONFIG_PATH_DAILY_FEED_ENABLED,
            $value,
            $storeId
        );
    }

    /**
     * Saves the Deltas enabled config
     *
     * @param string $value
     * @param integer $storeId
     *
     * @return void
     */
    public function setDeltasEnabled($value, $storeId)
    {
        $this->setConfigValue(
            self::CONFIG_PATH_PRODUCT_INDEX,
            $value,
            $storeId
        );
    }

    /**
     * Gets a config value from scopeConfig
     *
     * @param string $path - config path
     * @param string $scope - config scope
     * @param integer $storeId
     *
     * @return mixed
     */
    private function getConfigValue($path, $scope, $storeId = null)
    {
        return $this->scopeConfig->getValue($path, $scope, $storeId);
    }

    /**
     * Gets a config flag from scopeConfig
     *
     * @param string $path - config path
     * @param string $scope - config scope
     * @param integer $storeId
     *
     * @return boolean
     */
    private function getConfigFlag($path, $scope, $storeId = null)
    {
        return $this->scopeConfig->isSetFlag($path, $scope, $storeId);
    }

    /**
     * Saves a config value
     *
     * @param string $path - config path
     * @param mixed $value - new value for config
     * @param integer $storeId
     *
     * @return void
     */
    private function setConfigValue($path, $value, $storeId = 0)
    {
        if ($storeId === 0) {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        } else {
            $scope = ScopeInterface::SCOPE_STORES;
        }

        $this->configWriter->save($path, $value, $scope, $storeId);
    }
}
