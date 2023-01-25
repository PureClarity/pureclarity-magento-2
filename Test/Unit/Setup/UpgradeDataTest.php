<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Setup;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\State;
use Pureclarity\Core\Setup\UpgradeData;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;

/**
 * Class UpgradeDataTest
 *
 * Tests the methods in \Pureclarity\Core\Setup\UpgradeData
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpgradeDataTest extends TestCase
{
    /** @var UpgradeData $object */
    private $object;

    /** @var MockObject|ModuleDataSetupInterface $setup */
    private $setup;

    /** @var MockObject|ModuleContextInterface $context */
    private $context;

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
        $this->setup = $this->createMock(ModuleDataSetupInterface::class);
        $this->context = $this->createMock(ModuleContextInterface::class);
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->stateRepository = $this->createMock(StateRepositoryInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->object = new UpgradeData(
            $this->coreConfig,
            $this->stateRepository,
            $this->storeManager,
            $this->logger
        );
    }

    /**
     * Sets up the ModuleContextInterface getVersion with the provided version
     * @param string $version
     */
    private function setupGetVersion($version)
    {
        $this->context->expects($this->any())
            ->method('getVersion')
            ->willReturn($version);
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
        $this->assertInstanceOf(UpgradeData::class, $this->object);
    }

    /**
     * Tests class gets implements the correct interface
     */
    public function testInterface()
    {
        $this->assertInstanceOf(UpgradeDataInterface::class, $this->object);
    }

    /**
     * Tests that the 2.0.0 upgrade handles un-configured setup
     */
    public function testUpgrade200NotConfigured()
    {
        $this->setupGetVersion('1.0.0');
        $this->setupGetStores();

        $this->setup->expects($this->exactly(2))->method('startSetup');
        $this->setup->expects($this->exactly(2))->method('endSetup');

        $this->coreConfig->expects($this->any())
            ->method('getAccessKey')
            ->willReturn(null);

        $this->stateRepository->expects($this->any())
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->object->upgrade($this->setup, $this->context);
    }

    /**
     * Tests that the 2.0.0 upgrade handles configured setup on one store
     */
    public function testUpgrade200ConfiguredStore1()
    {
        $this->setupGetVersion('1.0.0');
        $this->setupGetStores();

        $this->setup->expects($this->exactly(2))->method('startSetup');
        $this->setup->expects($this->exactly(2))->method('endSetup');

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

        $this->object->upgrade($this->setup, $this->context);
    }

    /**
     * Tests that the 2.0.0 upgrade handles configured setup the second store
     */
    public function testUpgrade200ConfiguredStore2()
    {
        $this->setupGetVersion('1.0.0');
        $this->setupGetStores();

        $this->setup->expects($this->exactly(2))->method('startSetup');
        $this->setup->expects($this->exactly(2))->method('endSetup');

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

        $this->object->upgrade($this->setup, $this->context);
    }

    /**
     * Tests that the 2.0.0 upgrade handles an Exception
     */
    public function testUpgrade200Exception()
    {
        $this->setupGetVersion('1.0.0');
        $this->setupGetStores();

        $this->setup->expects($this->exactly(2))->method('startSetup');
        $this->setup->expects($this->exactly(2))->method('endSetup');

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

        $this->object->upgrade($this->setup, $this->context);
    }

    /**
     * Tests that the 3.0.0 upgrade only gets called if 2.0.0 is already installed
     */
    public function test300OnlyUpgrade()
    {
        $this->setupGetVersion('2.0.0');

        $this->setup->expects($this->once())->method('startSetup');
        $this->setup->expects($this->once())->method('endSetup');

        $this->stateRepository->expects($this->any())
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->object->upgrade($this->setup, $this->context);
    }

    /**
     * Tests that the 3.0.0 upgrade does the relevant deletes if data present
     */
    public function test300UpgradeDoesDeletes()
    {
        $this->setupGetVersion('2.0.0');

        $this->setup->expects($this->once())->method('startSetup');
        $this->setup->expects($this->once())->method('endSetup');

        $configuredState = $this->getStateMock(1, 'is_configured');
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($configuredState);

        $this->stateRepository->expects($this->at(1))
            ->method('delete')
            ->with($configuredState);

        $defaultState = $this->getStateMock(2, 'default_store');
        $this->stateRepository->expects($this->at(2))
            ->method('getByNameAndStore')
            ->with('default_store', 0)
            ->willReturn($defaultState);

        $this->stateRepository->expects($this->at(3))
            ->method('delete')
            ->with($defaultState);

        $signupState = $this->getStateMock(3, 'signup_request', 'complete');

        $this->stateRepository->expects($this->at(4))
            ->method('getByNameAndStore')
            ->with('signup_request', 0)
            ->willReturn($signupState);

        $this->stateRepository->expects($this->at(5))
            ->method('delete')
            ->with($signupState);

        $this->object->upgrade($this->setup, $this->context);
    }

    /**
     * Tests that the 3.0.0 upgrade handles a delete failure
     */
    public function test300UpgradeDoesDeleteError()
    {
        $this->setupGetVersion('2.0.0');

        $this->setup->expects($this->once())->method('startSetup');
        $this->setup->expects($this->once())->method('endSetup');

        $configuredState = $this->getStateMock(1, 'is_configured');
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($configuredState);

        $this->stateRepository->expects($this->at(1))
            ->method('delete')
            ->with($configuredState)
            ->willThrowException(new CouldNotDeleteException(new Phrase('Some delete error')));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('PureClarity: could not delete old state on upgrade: Some delete error');

        $this->object->upgrade($this->setup, $this->context);
    }

    /**
     * Tests that no upgrade happens with a high version number
     */
    public function testNoUpgrade()
    {
        $this->context->method('getVersion')
            ->willReturn('9.9.9');

        $this->setup->expects($this->never())->method('startSetup');
        $this->setup->expects($this->never())->method('endSetup');
        $this->object->upgrade($this->setup, $this->context);
    }
}
