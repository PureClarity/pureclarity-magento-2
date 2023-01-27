<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Setup;

use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\State;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Setup\Patch\Data\CheckForPreviousInstall;

/**
 * Class CheckForPreviousInstallTest
 *
 * Tests the methods in \Pureclarity\Core\Setup\Patch\Data\CheckForPreviousInstall
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckForPreviousInstallTest extends TestCase
{
    /** @var CheckForPreviousInstall $object */
    private $object;


    /** @var MockObject|ModuleDataSetupInterface $moduleDataSetupInterface */
    private $moduleDataSetupInterface;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /** @var MockObject|StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var MockObject|StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var MockObject|LoggerInterface $logger */
    private $logger;

    protected function setUp(): void
    {
        $this->moduleDataSetupInterface = $this->createMock(CoreConfig::class);

        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->stateRepository = $this->createMock(StateRepositoryInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->object = new CheckForPreviousInstall(
                $this->moduleDataSetupInterface,
                $this->coreConfig,
                $this->stateRepository,
                $this->storeManager,
                $this->logger
        );
    }

    /**
     * Sets up StoreManagerInterface getStores to return 2 stores
     */
    private function setupGetStores()
    {
        $store1 = $this->createMock(StoreInterface::class);

        $store1->expects($this->any())
            ->method('getId')
            ->willReturn('1');

        $store2 = $this->createMock(StoreInterface::class);

        $store2->expects($this->any())
            ->method('getId')
            ->willReturn('2');

        $this->storeManager->expects($this->once())
            ->method('getStores')
            ->willReturn([$store1, $store2]);
    }

    /**
     * Generates a State mock
     *
     * @param integer $id
     * @param string $name
     * @param string $value
     * @param string $storeId
     * @return MockObject
     */
    private function getStateMock($id = null, $name = null, $value = null, $storeId = null)
    {
        $state = $this->createMock(State::class);

        $state->method('getId')
            ->willReturn($id);

        $state->method('getStoreId')
            ->willReturn($storeId);

        $state->method('getName')
            ->willReturn($name);

        $state->method('getValue')
            ->willReturn($value);

        return $state;
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        $this->assertInstanceOf(CheckForPreviousInstall::class, $this->object);
    }

    /**
     * Tests class gets implements the correct interface
     */
    public function testInterface()
    {
        $this->assertInstanceOf(DataPatchInterface::class, $this->object);
    }

    /**
     * Tests that the upgrade handles un-configured setup
     */
    public function testUpgradeNotConfigured()
    {
        $this->setupGetStores();

        $this->moduleDataSetup->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->createMock(State::class));

        $this->coreConfig->expects($this->any())
            ->method('getAccessKey')
            ->willReturn(null);

        $this->stateRepository->expects($this->any())
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->object->apply();
    }

    /**
     * Tests that the upgrade handles configured setup on one store
     */
    public function testUpgradeConfiguredStore1()
    {
        $this->setupGetStores();

        $this->moduleDataSetup->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->createMock(State::class));

        $this->coreConfig->expects($this->at(0))
            ->method('getAccessKey')
            ->with(1)
            ->willReturn('ACCESSKEY1234');

        $stateMock = $this->getStateMock();

        $stateMock->expects($this->once())
            ->method('setName')
            ->with('is_configured');

        $stateMock->expects($this->once())
            ->method('setValue')
            ->with(1);

        $stateMock->expects($this->once())
            ->method('setStoreId')
            ->with(0);

        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($stateMock);

        $stateMock2 = $this->getStateMock();

        $stateMock2->expects($this->once())
            ->method('setName')
            ->with('default_store');

        $stateMock2->expects($this->once())
            ->method('setValue')
            ->with(1);

        $stateMock2->expects($this->once())
            ->method('setStoreId')
            ->with(0);

        $this->stateRepository->expects($this->at(2))
            ->method('getByNameAndStore')
            ->with('default_store', 0)
            ->willReturn($stateMock2);

        $this->stateRepository->expects($this->any())
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->object->apply();
    }

    /**
     * Tests that the upgrade handles configured setup the second store
     */
    public function testUpgradeConfiguredStore2()
    {
        $this->setupGetStores();

        $this->moduleDataSetup->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->createMock(State::class));

        $this->coreConfig->expects($this->at(0))
            ->method('getAccessKey')
            ->with(1);

        $this->coreConfig->expects($this->at(1))
            ->method('getAccessKey')
            ->with(2)
            ->willReturn('ACCESSKEY1234');

        $stateMock = $this->getStateMock();

        $stateMock->expects($this->once())
            ->method('setName')
            ->with('is_configured');

        $stateMock->expects($this->once())
            ->method('setValue')
            ->with(1);

        $stateMock->expects($this->once())
            ->method('setStoreId')
            ->with(0);

        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($stateMock);

        $stateMock2 = $this->getStateMock();

        $stateMock2->expects($this->once())
            ->method('setName')
            ->with('default_store');

        $stateMock2->expects($this->once())
            ->method('setValue')
            ->with(2);

        $stateMock2->expects($this->once())
            ->method('setStoreId')
            ->with(0);

        $this->stateRepository->expects($this->at(2))
            ->method('getByNameAndStore')
            ->with('default_store', 0)
            ->willReturn($stateMock2);

        $this->stateRepository->expects($this->any())
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->object->apply();
    }

    /**
     * Tests that the upgrade handles an Exception
     */
    public function testUpgradeException()
    {
        $this->setupGetStores();

        $this->moduleDataSetup->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->createMock(State::class));

        $this->coreConfig->expects($this->at(0))
            ->method('getAccessKey')
            ->with(1)
            ->willReturn('ACCESSKEY1234');

        $this->stateRepository->expects($this->any())
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($this->getStateMock(null, 'is_configured', '1', '0'));

        $this->stateRepository->expects($this->any())
            ->method('save')
            ->willThrowException(new CouldNotSaveException(new Phrase('An Error')));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('PureClarity: could not set state on upgrade: An Error');

        $this->object->apply();
    }
}