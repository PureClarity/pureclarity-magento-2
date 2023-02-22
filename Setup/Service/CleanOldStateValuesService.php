<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Pureclarity\Core\Setup\Service;

use Magento\Framework\Exception\CouldNotDeleteException;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;

/**
 * Cleans out old state values that are not used anymore.
 */
class CleanOldStateValuesService
{
    /**
     * @var StateRepositoryInterface
     */
    private StateRepositoryInterface $stateRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param StateRepositoryInterface $stateRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        StateRepositoryInterface $stateRepository,
        LoggerInterface $logger
    ) {
        $this->stateRepository = $stateRepository;
        $this->logger = $logger;
    }

    /**
     * Clean out old state values that are not used anymore.
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $configuredState = $this->stateRepository->getByNameAndStore('is_configured', 0);

            if ($configuredState->getId()) {
                $this->stateRepository->delete($configuredState);
            }
            $defaultStoreState = $this->stateRepository->getByNameAndStore('default_store', 0);

            if ($defaultStoreState->getId()) {
                $this->stateRepository->delete($defaultStoreState);
            }
            $signupState = $this->stateRepository->getByNameAndStore('signup_request', 0);

            if ($signupState->getId() && $signupState->getValue() === 'complete') {
                $this->stateRepository->delete($signupState);
            }
        } catch (CouldNotDeleteException $exception) {
            $this->logger->error(
                'PureClarity: could not delete old state on upgrade: ' . $exception->getMessage()
            );
        }
    }
}
