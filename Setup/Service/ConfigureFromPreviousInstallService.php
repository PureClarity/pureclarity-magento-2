<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Pureclarity\Core\Setup\Service;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Populates data in the pureclarity_state table if already configured.
 */
class ConfigureFromPreviousInstallService
{
    /**
     * @var CoreConfig
     */
    private CoreConfig $coreConfig;

    /**
     * @var StateRepositoryInterface
     */
    private StateRepositoryInterface $stateRepository;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CoreConfig $coreConfig
     * @param StateRepositoryInterface $stateRepository
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        CoreConfig $coreConfig,
        StateRepositoryInterface $stateRepository,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->coreConfig = $coreConfig;
        $this->stateRepository = $stateRepository;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Populate data in the pureclarity_state table if already configured.
     *
     * @return void
     */
    public function execute(): void
    {
        $stores = $this->storeManager->getStores();
        $configured = false;
        $configuredStoreId = null;

        foreach ($stores as $store) {
            $accessKey = $this->coreConfig->getAccessKey($store->getId());
            if ($accessKey) {
                $configured = true;
                $configuredStoreId = $store->getId();
                break;
            }
        }

        if ($configured && $configuredStoreId !== null) {
            try {
                $configuredState = $this->stateRepository->getByNameAndStore('is_configured', 0);
                $configuredState->setName('is_configured');
                $configuredState->setValue(1);
                $configuredState->setStoreId(0);
                $this->stateRepository->save($configuredState);
                $defaultStoreState = $this->stateRepository->getByNameAndStore('default_store', 0);
                $defaultStoreState->setName('default_store');
                $defaultStoreState->setValue($configuredStoreId);
                $defaultStoreState->setStoreId(0);
                $this->stateRepository->save($defaultStoreState);
            } catch (CouldNotSaveException $exception) {
                $this->logger->error(
                    'PureClarity: could not set state on upgrade: ' . $exception->getMessage()
                );
            }
        }
    }
}
