<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\CoreConfig;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CoreConfigTest
 *
 * Tests the methods in \Pureclarity\Core\Model\CoreConfig
 */
class CoreConfigTest extends TestCase
{
    /** @var CoreConfig $object */
    private $object;

    /** @var MockObject|ScopeConfigInterface $scopeConfig */
    private $scopeConfig;

    /** @var MockObject|WriterInterface $writer */
    private $writer;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->writer = $this->createMock(WriterInterface::class);

        $this->object = new CoreConfig($this->scopeConfig, $this->writer);
    }

    /**
     * Test that class set up correctly
     */
    public function testCoreConfigInstance(): void
    {
        $this->assertInstanceOf(CoreConfig::class, $this->object);
    }

    /**
     * Test that isActive returns false, when accesskey configureg & flag enabled
     */
    public function testIsActiveTrue(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $active = $this->object->isActive(1);
        $this->assertEquals(true, $active);
    }

    /**
     * Test that isActive returns false, when no accesskey in config
     */
    public function testIsActiveNoAccessKey(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);
        $active = $this->object->isActive(1);
        $this->assertEquals(false, $active);
    }

    /**
     * Test that isActive returns false, when disabled in config
     */
    public function testIsActiveFalse(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(false);
        $active = $this->object->isActive(1);
        $this->assertEquals(false, $active);
    }

    /**
     * Test that getAccessKey returns configured value
     */
    public function testGetAccessKey(): void
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $accessKey = $this->object->getAccessKey(1);
        $this->assertEquals('AccessKey', $accessKey);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $accessKey = $this->object->getAccessKey(1);
        $this->assertEquals(null, $accessKey);
    }

    /**
     * Test that getSecretKey returns configured value
     */
    public function testGetSecretKey(): void
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_SECRET_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('SecretKey');

        $result = $this->object->getSecretKey(1);
        $this->assertEquals('SecretKey', $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_SECRET_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getSecretKey(1);
        $this->assertEquals(null, $result);
    }

    /**
     * Test that getRegion always returns 1 (eu-west-1)
     */
    public function testGetRegion(): void
    {
        $result = $this->object->getRegion(1);
        $this->assertEquals(1, $result);
    }

    /**
     * Test that getMode returns null when not configured
     */
    public function testGetModeClientNoValue(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_MODE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getMode(1);
        $this->assertEquals(null, $result);
    }

    /**
     * Test that getMode returns a configured mode
     */
    public function testGetModeWithvalue(): void
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_MODE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('client');

        $result = $this->object->getMode(1);
        $this->assertEquals('client', $result);
    }

    /**
     * Test that isDailyFeedActive returns true when pc active & feed enabled
     */
    public function testIsDailyFeedActiveTrue(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_DAILY_FEED_ENABLED, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $result = $this->object->isDailyFeedActive(1);
        $this->assertEquals(true, $result);
    }

    /**
     * Test that isDailyFeedActive returns false when pc active & feed flag not enabled
     */
    public function testIsDailyFeedActiveFalse(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_DAILY_FEED_ENABLED, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $result = $this->object->isDailyFeedActive(1);
        $this->assertEquals(false, $result);
    }

    /**
     * Test that isDailyFeedActive returns false when pc not active
     */
    public function testIsDailyFeedActiveFalseNotActive(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->isDailyFeedActive(1);
        $this->assertEquals(false, $result);
    }

    /**
     * Test that areDeltasEnabled returns true when pc active & deltas enabled
     */
    public function testAreDeltasEnabledTrue(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_PRODUCT_INDEX, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $result = $this->object->areDeltasEnabled(1);
        $this->assertEquals(true, $result);
    }

    /**
     * Test that areDeltasEnabled returns false when pc active & deltas disabled
     */
    public function testAreDeltasEnabledFalse(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_PRODUCT_INDEX, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $result = $this->object->areDeltasEnabled(1);
        $this->assertEquals(false, $result);
    }

    /**
     * Test that areDeltasEnabled returns false when pc not active
     */
    public function testAreDeltasEnabledNotActive(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->areDeltasEnabled(1);
        $this->assertEquals(false, $result);
    }

    /**
     * Test that sendCustomerGroupPricing returns true when pc active & pricing flag enabled
     */
    public function testSendCustomerGroupPricingTrue(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_CUSTOMER_GROUP_PRICING, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $result = $this->object->sendCustomerGroupPricing(1);
        $this->assertEquals(true, $result);
    }

    /**
     * Test that sendCustomerGroupPricing returns false when pc active & pricing flag disabled
     */
    public function testSendCustomerGroupPricingFalse(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_CUSTOMER_GROUP_PRICING, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $result = $this->object->sendCustomerGroupPricing(1);
        $this->assertEquals(false, $result);
    }

    /**
     * Test that sendCustomerGroupPricing returns false when pc not active
     */
    public function testSendCustomerGroupPricingNotActive(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->sendCustomerGroupPricing(1);
        $this->assertEquals(false, $result);
    }

    /**
     * Test that isBrandFeedEnabled returns true when pc active & brand feed flag enabled
     */
    public function testBrandFeedEnabledTrue(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_BRAND_FEED_ENABLED, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $result = $this->object->isBrandFeedEnabled(1);
        $this->assertEquals(true, $result);
    }

    /**
     * Test that isBrandFeedEnabled returns false when pc active & brand feed flag disabled
     */
    public function testBrandFeedEnabledFalse(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_BRAND_FEED_ENABLED, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $result = $this->object->isBrandFeedEnabled(1);
        $this->assertEquals(false, $result);
    }

    /**
     * Test that isBrandFeedEnabled returns false when pc not active
     */
    public function testBrandFeedEnabledNotActive(): void
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->isBrandFeedEnabled(1);
        $this->assertEquals(false, $result);
    }

    /**
     * Test that getBrandParentCategory returns configrued value
     */
    public function testGetBrandParentCategory(): void
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_BRAND_CATEGORY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('SecretKey');

        $result = $this->object->getBrandParentCategory(1);
        $this->assertEquals('SecretKey', $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_BRAND_CATEGORY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getBrandParentCategory(1);
        $this->assertEquals(null, $result);
    }

    /**
     * Tests getExcludedProductAttributes returns configured value
     */
    public function testGetExcludedProductAttributesValue(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_EXCLUDED_PRODUCT_ATTRIBUTES, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('1,2,3');

        $this->assertEquals('1,2,3', $this->object->getExcludedProductAttributes(1));
    }

    /**
     * Tests getExcludedProductAttributes returns null if no configured value
     */
    public function testGetExcludedProductAttributesNoValue(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_EXCLUDED_PRODUCT_ATTRIBUTES, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $this->assertEquals(null, $this->object->getExcludedProductAttributes(1));
    }

    /**
     * Tests getExcludeOutOfStockFromRecommenders returns false if disabled in config
     */
    public function testGetExcludeOutOfStockFromRecommendersFalse(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_EXCLUDE_OOS_PRODUCTS_RECS, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $this->assertEquals(false, $this->object->getExcludeOutOfStockFromRecommenders(1));
    }

    /**
     * Tests getExcludeOutOfStockFromRecommenders returns true if enabled in config
     */
    public function testGetExcludeOutOfStockFromRecommendersTrue(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_EXCLUDE_OOS_PRODUCTS_RECS, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $this->assertEquals(true, $this->object->getExcludeOutOfStockFromRecommenders(1));
    }

    /**
     * Tests getProductPlaceholderUrl returns configured value
     */
    public function testGetProductPlaceholderUrl(): void
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_PLACEHOLDER_PRODUCT, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('SecretKey');

        $result = $this->object->getProductPlaceholderUrl(1);
        $this->assertEquals('SecretKey', $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_PLACEHOLDER_PRODUCT, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getProductPlaceholderUrl(1);
        $this->assertEquals(null, $result);
    }

    /**
     * Tests getCategoryPlaceholderUrl returns configured value
     */
    public function testGetCategoryPlaceholderUrl(): void
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_PLACEHOLDER_CATEGORY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('SecretKey');

        $result = $this->object->getCategoryPlaceholderUrl(1);
        $this->assertEquals('SecretKey', $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_PLACEHOLDER_CATEGORY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getCategoryPlaceholderUrl(1);
        $this->assertEquals(null, $result);
    }

    /**
     * Tests getSecondaryCategoryPlaceholderUrl returns configured value
     */
    public function testGetSecondaryCategoryPlaceholderUrl(): void
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_PLACEHOLDER_CATEGORY_SECONDARY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('SecretKey');

        $result = $this->object->getSecondaryCategoryPlaceholderUrl(1);
        $this->assertEquals('SecretKey', $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_PLACEHOLDER_CATEGORY_SECONDARY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getSecondaryCategoryPlaceholderUrl(1);
        $this->assertEquals(null, $result);
    }

    /**
     * Tests isZoneDebugActive returns configured value
     */
    public function testIsZoneDebugActive(): void
    {
        // Test with value = true
        $this->scopeConfig->expects($this->at(0))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ZONE_DEBUG, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $result = $this->object->isZoneDebugActive(1);
        $this->assertEquals(true, $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ZONE_DEBUG, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $result = $this->object->isZoneDebugActive(1);
        $this->assertEquals(false, $result);
    }

    /**
     * Tests getNumberSwatchesPerProduct returns configured value
     */
    public function testGetNumberSwatchesPerProduct(): void
    {
        // Test with value = true
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_SWATCHES_PER_PRODUCT, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('16');

        $result = $this->object->getNumberSwatchesPerProduct(1);
        $this->assertEquals('16', $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_SWATCHES_PER_PRODUCT, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getNumberSwatchesPerProduct(1);
        $this->assertEquals(null, $result);
    }

    /**
     * Tests showSwatches returns configured value
     */
    public function testShowSwatches(): void
    {
        // Test with value = true
        $this->scopeConfig->expects($this->at(0))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_SWATCHES_IN_PRODUCT_LIST, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $result = $this->object->showSwatches(1);
        $this->assertEquals(true, $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_SWATCHES_IN_PRODUCT_LIST, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $result = $this->object->showSwatches(1);
        $this->assertEquals(false, $result);
    }

    /**
     * Tests isDebugLoggingEnabled returns false if disabled in config
     */
    public function testIsDebugLoggingEnabledFalse(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_DEBUG_LOGGING, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null)
            ->willReturn(false);

        $this->assertEquals(false, $this->object->isDebugLoggingEnabled());
    }

    /**
     * Tests isDebugLoggingEnabled returns true if enabled in config
     */
    public function testIsDebugLoggingEnabledTrue(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_DEBUG_LOGGING, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null)
            ->willReturn(true);

        $this->assertEquals(true, $this->object->isDebugLoggingEnabled());
    }

    /**
     * Tests setAccessKey passes correct values to Magento config at the default store level
     */
    public function testSetAccessKeyWithDefault(): void
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, 'ABCDE', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

        $this->object->setAccessKey('ABCDE', 0);
    }

    /**
     * Tests setAccessKey passes correct values to Magento config at the store level
     */
    public function testSetAccessKeyStore(): void
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, 'ABCDE', ScopeInterface::SCOPE_STORES, 1);

        $this->object->setAccessKey('ABCDE', 1);
    }

    /**
     * Tests setSecretKey passes correct values to Magento config
     */
    public function testSetSecretKey(): void
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_SECRET_KEY, 'ABCDE', ScopeInterface::SCOPE_STORES, 1);

        $this->object->setSecretKey('ABCDE', 1);
    }

    /**
     * Tests setRegion passes correct values to Magento config
     */
    public function testSetRegion(): void
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_REGION, 1, ScopeInterface::SCOPE_STORES, 1);

        $this->object->setRegion(1, 1);
    }

    /**
     * Tests setIsActive passes correct values to Magento config
     */
    public function testSetIsActive(): void
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, 1, ScopeInterface::SCOPE_STORES, 1);

        $this->object->setIsActive(1, 1);
    }

    /**
     * Tests setIsDailyFeedActive passes correct values to Magento config
     */
    public function testSetIsDailyFeedActive(): void
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_DAILY_FEED_ENABLED, 1, ScopeInterface::SCOPE_STORES, 1);

        $this->object->setIsDailyFeedActive(1, 1);
    }

    /**
     * Tests setDeltasEnabled passes correct values to Magento config
     */
    public function testSetDeltasEnabled(): void
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_PRODUCT_INDEX, 1, ScopeInterface::SCOPE_STORES, 1);

        $this->object->setDeltasEnabled(1, 1);
    }
}
