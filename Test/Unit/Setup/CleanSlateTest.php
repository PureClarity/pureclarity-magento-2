<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Setup;

use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\State;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Setup\Patch\Data\CleanSlate;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class CleanSlateTest
 *
 * Tests the methods in \Pureclarity\Core\Setup\Patch\Data\CleanSlate
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CleanSlateTest extends TestCase
{
    /** @var CleanSlate $object */
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
        $this->moduleDataSetupInterface = $this->createMock(ModuleDataSetupInterface::class);

        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->stateRepository = $this->createMock(StateRepositoryInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->object = new CleanSlate(
                $this->moduleDataSetupInterface,
                $this->coreConfig,
                $this->stateRepository,
                $this->storeManager,
                $this->logger
        );
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
        $this->assertInstanceOf(CleanSlate::class, $this->object);
    }

    /**
     * Tests class gets implements the correct interface
     */
    public function testInterface()
    {
        $this->assertInstanceOf(DataPatchInterface::class, $this->object);
    }

    /**
     * Tests that the upgrade does the relevant deletes if data present
     */
    public function testUpgradeDoesDeletes()
    {

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

        $this->object->apply();
    }

    /**
     * Tests that the upgrade handles a delete failure
     */
    public function testUpgradeDoesDeleteError()
    {
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

        $this->object->apply();
    }
}