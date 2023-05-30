<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Category;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\Type\Category\RowData;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Catalog\Model\Category;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class RowDataTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Category\RowData
 */
class RowDataTest extends TestCase
{
    /** @var int */
    private const STORE_ID = 1;

    /** @var RowData */
    private $object;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /** @var MockObject|LoggerInterface */
    private $logger;

    protected function setUp(): void
    {
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->object = new RowData(
            $this->coreConfig,
            $this->logger
        );
    }

    /**
     * Sets up config value loading
     * @param bool $configured
     */
    public function setupConfig(bool $configured = false): void
    {
        if ($configured) {
            $this->coreConfig->expects(self::once())
                ->method('getCategoryPlaceholderUrl')
                ->with(self::STORE_ID)
                ->willReturn('//www.example.com/placeholder-image-url.jpg');

            $this->coreConfig->expects(self::once())
                ->method('getSecondaryCategoryPlaceholderUrl')
                ->with(self::STORE_ID)
                ->willReturn('//www.example.com/secondary-placeholder-image-url.jpg');
        } else {
            $this->coreConfig->expects(self::once())
                ->method('getCategoryPlaceholderUrl')
                ->with(self::STORE_ID)
                ->willReturn('');

            $this->coreConfig->expects(self::once())
                ->method('getSecondaryCategoryPlaceholderUrl')
                ->with(self::STORE_ID)
                ->willReturn('');
        }
    }

    /**
     * Builds dummy data for category feed
     * @param int $categoryId
     * @return array
     */
    public function mockCategoryData(int $categoryId): array
    {
        // default: a category with the base information present
        $categoryData = [
            'Id' => $categoryId,
            'DisplayName' => 'Category ' . $categoryId,
            'Image' => '',
            'Description' => '',
            'Link' => '/',
            'ParentIds' => [],
        ];

        // a lower-level category, with all optional fields being sent
        if ($categoryId === 2) {
            // has an image
            $categoryData['Image'] = '//www.example.com/category-' . $categoryId . 'image-url.jpg';
            // has a description
            $categoryData['Description'] = 'A Description of ' . $categoryId;
            // level > 1 so has a link
            $categoryData['Link'] = '//www.example.com/category-' . $categoryId;
            // level > 1 so has a parent
            $categoryData['ParentIds'] = ['7'];
            // is excluded from recommenders
            $categoryData['ExcludeFromRecommenders'] = true;
            // is not active
            $categoryData['IsActive'] = false;
            // has an override image set
            $categoryData['OverrideImage'] = '//www.example.com/'
                . 'catalog/pureclarity_category_image/'
                . 'override-image-url.jpg';
        }

        // a lower-level category, with placeholder images being sent
        if ($categoryId === 3) {
            // level > 1 so has a link
            $categoryData['Link'] = '//www.example.com/category-' . $categoryId;
            // level > 1 so has a parent
            $categoryData['ParentIds'] = ['7'];
            // no image, but placeholder configured
            $categoryData['Image'] = '//www.example.com/placeholder-image-url.jpg';
            // no override image, but placeholder configured
            $categoryData['OverrideImage'] = '//www.example.com/secondary-placeholder-image-url.jpg';
        }

        // a category with no name
        if ($categoryId === 4) {
            //no name, so defaults to category id
            $categoryData['DisplayName'] = $categoryId;
            // no name, so set to be excluded from recommenders
            $categoryData['ExcludeFromRecommenders'] = true;
        }

        return $categoryData;
    }

    /**
     * Sets up a parent category MockObject
     * @return MockObject|Category
     */
    public function setupParentCategory()
    {
        $category = $this->createPartialMock(
            Category::class,
            [
                'getId'
            ]
        );

        $category->method('getId')
            ->willReturn('7');

        return $category;
    }

    /**
     * Sets up a base category MockObject
     * @param int $categoryId
     * @return MockObject|Category
     */
    public function setupBaseCategory(int $id, string $name, int $level)
    {
        $category = $this->createPartialMock(
            Category::class,
            [
                'getId',
                'getName',
                'getData',
                'getImageUrl',
                'getIsActive',
                'getLevel',
                'getParentCategory',
                'getUrl'
            ]
        );

        $category->method('getId')
            ->willReturn($id);

        $category->method('getName')
            ->willReturn($name);

        $category->method('getLevel')
            ->willReturn($level);

        return $category;
    }
    /**
     * Sets up a category MockObject with the following requirements:
     *
     * default: a category with the base information present (only name & Id will be populated)
     *
     * @return MockObject|Category
     */
    public function setupCategory1()
    {
        $categoryId = 1;
        $category = $this->setupBaseCategory($categoryId, 'Category ' . $categoryId, $categoryId);

        $category->expects(self::at(5))
            ->method('getData')
            ->with('description')
            ->willReturn('');

        $category->method('getImageUrl')
            ->willReturn(null);

        $category->expects(self::at(7))
            ->method('getData')
            ->with('pureclarity_hide_from_feed')
            ->willReturn('0');

        $category->method('getIsActive')
            ->willReturn('1');

        $category->expects(self::at(9))
            ->method('getData')
            ->with('pureclarity_category_image')
            ->willReturn(null);

        return $category;
    }

    /**
     * Sets up a category MockObject with the following requirements:
     *
     * has an image set
     * has a description
     * level > 1 so has a link
     * level > 1 so has a parent
     * is excluded from recommenders
     * is not active
     * has an override image set
     *
     * @return MockObject|Category
     */
    public function setupCategory2()
    {
        $categoryId = 2;
        $category = $this->setupBaseCategory($categoryId, 'Category ' . $categoryId, $categoryId);

        $category->method('getImageUrl')
            ->willReturn('//www.example.com/category-' . $categoryId . 'image-url.jpg');

        $category->expects(self::at(5))
            ->method('getData')
            ->with('description')
            ->willReturn('A Description of ' . $categoryId);

        $category->method('getParentCategory')
            ->willReturn($this->setupParentCategory());

        $category->method('getUrl')
            ->willReturn('//www.example.com/category-' . $categoryId);

        $category->expects(self::at(9))
            ->method('getData')
            ->with('pureclarity_hide_from_feed')
            ->willReturn('1');

        $category->method('getIsActive')
            ->willReturn('0');

        $category->expects(self::at(11))
            ->method('getData')
            ->with('pureclarity_category_image')
            ->willReturn('override-image-url.jpg');

        return $category;
    }

    /**
     * Sets up a category MockObject with the following requirements:
     *
     * level > 1 so has a link
     * level > 1 so has a parent
     * no image
     * no override image
     *
     * @return MockObject|Category
     */
    public function setupCategory3()
    {
        $categoryId = 3;
        $category = $this->setupBaseCategory($categoryId, 'Category ' . $categoryId, $categoryId);

        $category->method('getImageUrl')
            ->willReturn(null);

        $category->expects(self::at(5))
            ->method('getData')
            ->with('description')
            ->willReturn('');

        $category->method('getParentCategory')
            ->willReturn($this->setupParentCategory());

        $category->method('getUrl')
            ->willReturn('//www.example.com/category-' . $categoryId);

        $category->expects(self::at(9))
            ->method('getData')
            ->with('pureclarity_hide_from_feed')
            ->willReturn('0');

        $category->method('getIsActive')
            ->willReturn('1');

        $category->expects(self::at(11))
            ->method('getData')
            ->with('pureclarity_category_image')
            ->willReturn('');

        return $category;
    }

    /**
     * Sets up a category MockObject with the following requirements:
     *
     * no name
     *
     * @return MockObject|Category
     */
    public function setupCategory4()
    {
        $categoryId = 1;
        $category = $this->setupBaseCategory($categoryId, null, $categoryId);

        $category->method('getImageUrl')
            ->willReturn(null);

        $category->expects(self::at(5))
            ->method('getData')
            ->with('description')
            ->willReturn('');

        $category->method('getParentCategory')
            ->willReturn($this->setupParentCategory());

        $category->method('getUrl')
            ->willReturn('//www.example.com/category-' . $categoryId);

        $category->expects(self::at(9))
            ->method('getData')
            ->with('pureclarity_hide_from_feed')
            ->willReturn('0');

        $category->method('getIsActive')
            ->willReturn('1');

        $category->expects(self::at(11))
            ->method('getData')
            ->with('pureclarity_category_image')
            ->willReturn('');

        return $category;
    }

    /**
     * Sets up a StoreInterface
     *
     * @return StoreInterface|MockObject
     */
    public function setupStore()
    {
        $store = $this->getMockForAbstractClass(
            StoreInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getId', 'getBaseUrl']
        );

        $store->method('getId')
            ->willReturn(1);

        $store->method('getBaseUrl')
            ->willReturn('http://www.example.com/');

        return $store;
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(RowData::class, $this->object);
    }

    /**
     * Tests that a row of data is processed correctly
     */
    public function testGetRowData(): void
    {
        $store = $this->setupStore();
        $this->setupConfig();
        $data = $this->mockCategoryData(1);
        $category = $this->setupCategory1();
        $rowData = $this->object->getRowData($store, $category);
        self::assertEquals($data, $rowData);
    }

    /**
     * Tests that a row of data with all the optional fields present is sent correctly
     */
    public function testGetRowDataWithOptional(): void
    {
        $store = $this->setupStore();
        $data = $this->mockCategoryData(2);
        $category = $this->setupCategory2();
        $rowData = $this->object->getRowData($store, $category);
        self::assertEquals($data, $rowData);
    }

    /**
     * Tests that a row of data with all the optional fields present is sent correctly
     */
    public function testGetRowDataWithPlaceholders(): void
    {
        $store = $this->setupStore();
        $this->setupConfig(true);
        $data = $this->mockCategoryData(3);
        $category = $this->setupCategory3();
        $rowData = $this->object->getRowData($store, $category);
        self::assertEquals($data, $rowData);
    }

    /**
     * Tests that a row of data with all the optional fields present is sent correctly
     */
    public function testGetRowDataWithNoName(): void
    {
        $store = $this->setupStore();
        $this->setupConfig(true);
        $data = $this->mockCategoryData(4);
        $category = $this->setupCategory4();
        $rowData = $this->object->getRowData($store, $category);
        self::assertEquals($data, $rowData);
    }
}