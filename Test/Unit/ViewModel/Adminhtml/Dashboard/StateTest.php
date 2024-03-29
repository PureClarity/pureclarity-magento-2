<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml\Dashboard;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\State;
use Pureclarity\Core\Model\State as StateModel;

/**
 * Class StateTest
 *
 * Tests the methods in \Pureclarity\Core\ViewModel\Adminhtml\Dashboard\State
 */
class StateTest extends TestCase
{
    /** @var State $object */
    private $object;

    /** @var MockObject|StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var MockObject|ProductMetadataInterface $productMetadata */
    private $productMetadata;

    /** @var MockObject|CoreConfig $productMetadata */
    private $coreConfig;

    protected function setUp(): void
    {
        $this->stateRepository = $this->createMock(StateRepositoryInterface::class);
        $this->productMetadata = $this->createMock(ProductMetadataInterface::class);

        $request = $this->createMock(RequestInterface::class);
        $this->coreConfig = $this->createMock(CoreConfig::class);

        $this->object = new State(
            $this->stateRepository,
            $this->productMetadata,
            $request,
            $this->coreConfig
        );
    }

    /**
     * Generates a State mock
     * @param string $id
     * @param string $name
     * @param string $value
     * @param string $storeId
     * @return MockObject
     */
    private function getStateMock($id = null, $name = null, $value = null, $storeId = null)
    {
        $state = $this->createMock(StateModel::class);

        $state->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $state->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $state->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        $state->expects($this->any())
            ->method('getValue')
            ->willReturn($value);

        return $state;
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        $this->assertInstanceOf(State::class, $this->object);
    }

    /**
     * Tests getStateName returns correct value when module is not configured on a store
     */
    public function testGetStateNameNotConfigured()
    {
        $this->stateRepository->expects($this->atMost(2))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->assertEquals(State::STATE_NOT_CONFIGURED, $this->object->getStateName(1));
    }

    /**
     * Tests getStateName returns correct value when module is waiting for a signup on a store
     */
    public function testGetStateNameWaiting()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'signup_request', 'notcomplete', '0'));

        $this->assertEquals(State::STATE_WAITING, $this->object->getStateName(1));
    }

    /**
     * Tests getStateName returns correct value when module is configured on a store
     */
    public function testGetStateNameConfigured()
    {
        $this->coreConfig->expects($this->at(0))
            ->method('getAccessKey')
            ->with(1)
            ->willReturn('ABCDEFGHI');

        $this->coreConfig->expects($this->at(1))
            ->method('getSecretKey')
            ->with(1)
            ->willReturn('ABCDEFGHIJKLMNOP');

        $this->assertEquals(State::STATE_CONFIGURED, $this->object->getStateName(1));
    }

    /**
     * Tests isWaiting returns correct value when module is waiting on a signup on a store
     */
    public function testIsWaitingTrue()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'signup_request', 'notcomplete', '0'));

        $this->assertEquals(true, $this->object->isWaiting(1));
    }

    /**
     * Tests isWaiting returns correct value when module is not waiting on a signup on a store
     */
    public function testIsWaitingFalse()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->assertEquals(false, $this->object->isWaiting(1));
    }

    /**
     * Tests getPluginVersion returns correct version of the plugin
     */
    public function testGetPluginVersion()
    {
        $this->assertEquals(Data::CURRENT_VERSION, $this->object->getPluginVersion());
    }

    /**
     * Tests isUpToDate returns correct flag when no github version is stored
     */
    public function testIsUpToDateTrueEmptyNewVersion()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->assertEquals(true, $this->object->isUpToDate());
    }

    /**
     * Tests isUpToDate returns correct flag when module version matches github version
     */
    public function testIsUpToDateTrueNewVersionMatches()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'new_version', Data::CURRENT_VERSION, '0'));

        $this->assertEquals(true, $this->object->isUpToDate());
    }

    /**
     * Tests isUpToDate returns correct flag when github version is higher
     */
    public function testIsUpToDateFalse()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'new_version', '9.9.9', '0'));

        $this->assertEquals(false, $this->object->isUpToDate());
    }

    /**
     * Tests getNewVersion returns correct version when one is stores
     */
    public function testGetNewVersion()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'new_version', '9.9.9', '0'));

        $this->assertEquals('9.9.9', $this->object->getNewVersion());
    }

    /**
     * Tests getNewVersion returns correct version when none is stored
     */
    public function testGetNewVersionEmpty()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->assertEquals('', $this->object->getNewVersion());
    }

    /**
     * Tests getMagentoVersion returns correct version of Magento
     */
    public function testGetMagentoVersion()
    {
        $this->productMetadata->expects($this->at(0))
            ->method('getVersion')
            ->willReturn('2.3.2');

        $this->productMetadata->expects($this->at(1))
            ->method('getEdition')
            ->willReturn('Commerce');

        $this->assertEquals('2.3.2 Commerce', $this->object->getMagentoVersion());
    }
}
