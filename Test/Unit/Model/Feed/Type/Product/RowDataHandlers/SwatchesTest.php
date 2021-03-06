<?php
declare(strict_types=1);

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Product\RowDataHandlers;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Swatches;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Framework\Serialize\SerializerInterface;
use ReflectionException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Catalog\Model\Product;
use Magento\Swatches\Block\Product\Renderer\Listing\Configurable;

/**
 * Class SwatchesTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Swatches
 * @see \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Swatches
 */
class SwatchesTest extends TestCase
{
    /** @var BlockFactory | MockObject */
    private $blockFactory;

    /** @var SerializerInterface | MockObject */
    private $serializer;

    /** @var StoreInterface | MockObject */
    private $store;

    /** @var Swatches */
    private $swatches;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->blockFactory = $this->createMock(BlockFactory::class);
        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->serializer->method('serialize')->willReturnCallback(function ($param) {
            $string = '';
            foreach ($param as $key => $value) {
                $string .= $key . ':' . trim(var_export($value, true), '\'') . '|';
            }
            return $string;
        });

        $this->serializer->method('unserialize')->willReturnCallback(function ($param) {
            return $param;
        });

        $this->store = $this->createMock(StoreInterface::class);
        $this->swatches = new Swatches(
            $this->blockFactory,
            $this->serializer
        );
    }

    /**
     * Sets up a StoreInterface
     *
     * @return StoreInterface|MockObject
     * @throws ReflectionException
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
        self::assertInstanceOf(Swatches::class, $this->swatches);
    }

    /**
     * Tests that getSwatchData returns the data generated by magento classes as expected
     * @throws ReflectionException
     */
    public function testGetSwatchData(): void
    {
        $store = $this->setupStore();
        $product = $this->createMock(Product::class);

        $configurableBlock = $this->createMock(Configurable::class);

        $configurableBlock->expects(self::once())
            ->method('setData')
            ->with('product', $product)
            ->willReturn($configurableBlock);

        $configurableBlock->expects(self::once())
            ->method('getJsonConfig')
            ->willReturn('{"something"}');

        $configurableBlock->expects(self::once())
            ->method('getNumberSwatchesPerProduct')
            ->willReturn('7');

        $configurableBlock->expects(self::once())
            ->method('getJsonSwatchConfig')
            ->willReturn('jsonSwatchConfig');

        $this->blockFactory->method('createBlock')
            ->with(Configurable::class)
            ->willReturn($configurableBlock);

        self::assertEquals(
            [
                'jsonconfig' => '{"something"}',
                'swatchrenderjson' => 'selectorProduct:.product-item-details|'
                                    . 'onlySwatches:true|'
                                    . 'enableControlLabel:false|'
                                    . 'numberToShow:7|'
                                    . 'jsonConfig:{"something"}|'
                                    . 'jsonSwatchConfig:jsonSwatchConfig|'
                                    . 'mediaCallback:http://www.example.com/swatches/ajax/media/|'
            ],
            $this->swatches->getSwatchData($store, $product)
        );
    }
}
