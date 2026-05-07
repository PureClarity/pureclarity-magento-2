<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml\Dashboard;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Config\Source\Region;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Regions;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class RegionsTest
 *
 * Tests the methods in \Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Regions
 */
class RegionsTest extends TestCase
{
    /** @var Regions $object */
    private $object;

    /** @var MockObject|Region $region */
    private $region;

    protected function setUp(): void
    {
        $this->region = $this->createMock(Region::class);

        $this->object = new Regions(
            $this->region
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Regions::class, $this->object);
    }

    public function testGetPureClarityRegions()
    {
        $expected = [
            [
                'label' => 'Europe',
                'value' => 1
            ]
        ];

        $this->region->expects($this->once())
            ->method('toOptionArray')
            ->willReturn($expected);

        $this->assertEquals($expected, $this->object->getPureClarityRegions());
    }
}
