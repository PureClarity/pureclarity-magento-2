<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Dashboard;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Dashboard\Banner;
use Pureclarity\Core\Model\State;

/**
 * Class BannerTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Dashboard\Banner
 */
class BannerTest extends TestCase
{
    /** @var Banner $object */
    private $object;

    /** @var MockObject|StateRepositoryInterface */
    private $stateRepository;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->stateRepository = $this->createMock(StateRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->object = new Banner(
            $this->stateRepository,
            $this->logger
        );
    }

    /**
     * @param int|null $stateId
     * @param string|null $name
     * @param string|null $value
     * @param int|null $storeId
     * @return State|MockObject
     * @throws \ReflectionException
     */
    private function getStateMock(int $stateId, string $name, string $value, int $storeId)
    {
        $state = $this->createMock(State::class);
        $state->method('getId')->willReturn($stateId);
        $state->method('getStoreId')->willReturn($storeId);
        $state->method('getName')->willReturn($name);
        $state->method('getValue')->willReturn($value);

        return $state;
    }

    /**
     * Sets up a State object for the banner state row
     * @param int $stateId
     * @return MockObject|State
     * @throws \ReflectionException
     */
    public function setupBannerState(int $stateId)
    {
        $bannerState = $this->getStateMock($stateId, 'show_welcome_banner', '', 1);

        $this->stateRepository->expects(self::at(0))
            ->method('getByNameAndStore')
            ->with('show_welcome_banner', 1)
            ->willReturn($bannerState);

        return $bannerState;
    }

    /**
     * Sets up a State object for the banner state row
     * @return MockObject|State
     * @throws \ReflectionException
     */
    public function setupGettingStartedState()
    {
        $gettingStartedState = $this->getStateMock(1, 'show_getting_started_banner', '', 1);

        $gettingStartedState->expects(self::once())
            ->method('setName')
            ->with('show_getting_started_banner');

        $gettingStartedState->expects(self::once())
            ->method('setStoreId')
            ->with(1);

        $this->stateRepository->expects(self::at(1))
            ->method('getByNameAndStore')
            ->with('show_getting_started_banner', 1)
            ->willReturn($gettingStartedState);

        return $gettingStartedState;
    }

    /**
     * Test class is set up correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Banner::class, $this->object);
    }

    /**
     * Tests that removeWelcomeBanner removes the welcome banner flag as expected
     * @throws \ReflectionException
     */
    public function testRemoveWelcomeBanner(): void
    {
        $bannerState = $this->setupBannerState(1);
        $gettingStartedState = $this->setupGettingStartedState();

        $this->stateRepository->expects(self::at(2))
            ->method('save')
            ->with($gettingStartedState);

        $this->stateRepository->expects(self::at(3))
            ->method('delete')
            ->with($bannerState);

        $this->object->removeWelcomeBanner(1);
    }

    /**
     * Tests that removeWelcomeBanner does nothing if no the welcome banner flag is set
     * @throws \ReflectionException
     */
    public function testRemoveWelcomeBannerNothingToDo(): void
    {
        $this->setupBannerState(0);

        $this->stateRepository->expects(self::never())
            ->method('save');

        $this->stateRepository->expects(self::never())
            ->method('delete');

        $this->object->removeWelcomeBanner(1);
    }

    /**
     * Tests that removeWelcomeBanner removes the welcome banner flag as expected
     * @throws \ReflectionException
     */
    public function testRemoveWelcomeBannerSaveException(): void
    {
        $this->setupBannerState(1);
        $gettingStartedState = $this->setupGettingStartedState();

        $this->stateRepository->expects(self::at(2))
            ->method('save')
            ->with($gettingStartedState)
            ->willThrowException(new CouldNotSaveException(new Phrase('An error')));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not save banner status: An error');

        $this->object->removeWelcomeBanner(1);
    }

    /**
     * Tests that removeWelcomeBanner removes the welcome banner flag as expected
     * @throws \ReflectionException
     */
    public function testRemoveWelcomeBannerDeleteException(): void
    {
        $bannerState = $this->setupBannerState(1);
        $this->setupGettingStartedState();

        $this->stateRepository->expects(self::at(3))
            ->method('delete')
            ->with($bannerState)
            ->willThrowException(new CouldNotDeleteException(new Phrase('An error')));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not delete banner flags: An error');

        $this->object->removeWelcomeBanner(1);
    }
}
