<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Signup;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\CoreConfig;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\Requester;

/**
 * Class Process
 *
 * model for processing signup requests
 */
class Process
{
    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var Manager $cacheManager */
    private $cacheManager;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var Requester $feedRequest */
    private $feedRequest;

    /**
     * @param StateRepositoryInterface $stateRepository
     * @param CoreConfig $coreConfig
     * @param StoreManagerInterface $storeManager
     * @param Manager $cacheManager
     * @param LoggerInterface $logger
     * @param Requester $feedRequest
     */
    public function __construct(
        StateRepositoryInterface $stateRepository,
        CoreConfig $coreConfig,
        StoreManagerInterface $storeManager,
        Manager $cacheManager,
        LoggerInterface $logger,
        Requester $feedRequest
    ) {
        $this->stateRepository = $stateRepository;
        $this->coreConfig      = $coreConfig;
        $this->storeManager    = $storeManager;
        $this->cacheManager    = $cacheManager;
        $this->logger          = $logger;
        $this->feedRequest     = $feedRequest;
    }

    /**
     * Processes the signup request
     *
     * @param mixed[] $requestData
     *
     * @return mixed[]
     */
    public function process($requestData)
    {
        $result = [
            'errors' => []
        ];

        try {
            $this->saveConfig($requestData);
            $this->setWelcomeState('auto', $requestData['store_id']);
            $this->completeSignup((int)$requestData['store_id']);
            $this->triggerFeeds((int)$requestData['store_id']);
        } catch (CouldNotSaveException $e) {
            $result['errors'][] = __('Error processing request: %1', $e->getMessage());
        }

        return $result;
    }

    /**
     * Processes a manual configuration from the dashboard page
     *
     * @param mixed[] $requestData
     *
     * @return mixed[]
     */
    public function processManualConfigure($requestData)
    {
        $result = [
            'errors' => []
        ];

        $result['errors'] = $this->validateManualConfigure($requestData);

        if (empty($result['errors'])) {
            try {
                $this->saveConfig($requestData);
                $this->setWelcomeState('manual', (int)$requestData['store_id']);
                $this->triggerFeeds((int)$requestData['store_id']);
            } catch (CouldNotSaveException $e) {
                $result['errors'][] = __('Error processing request: %1', $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Validates the params in the manual configure request
     *
     * @param mixed[] $requestData
     * @return array
     */
    private function validateManualConfigure($requestData)
    {
        $errors = [];

        if (!isset($requestData['access_key']) || empty($requestData['access_key'])) {
            $errors[] = __('Missing Access Key');
        }

        if (!isset($requestData['secret_key']) || empty($requestData['secret_key'])) {
            $errors[] = __('Missing Secret Key');
        }

        if (!isset($requestData['store_id'])) {
            $errors[] = __('Missing Store ID');
        }

        return $errors;
    }

    /**
     * Saves the PureClarity credentials to the Magento config
     *
     * @param mixed[] $requestData
     */
    private function saveConfig($requestData)
    {
        $storeId = (int)$requestData['store_id'];
        $this->coreConfig->setAccessKey($requestData['access_key'], $storeId);
        $this->coreConfig->setSecretKey($requestData['secret_key'], $storeId);
        $this->coreConfig->setRegion(1, $storeId);
        $this->coreConfig->setIsActive(1, $storeId);
        $this->coreConfig->setDeltasEnabled(1, $storeId);
        $this->coreConfig->setIsDailyFeedActive(1, $storeId);
        $this->cacheManager->clean([CacheTypeConfig::TYPE_IDENTIFIER]);
    }

    /**
     * Saves the is_configured flag
     *
     * @param string $type
     * @param int $storeId
     * @return void
     * @throws CouldNotSaveException
     */
    private function setWelcomeState($type, $storeId)
    {
        $state = $this->stateRepository->getByNameAndStore('show_welcome_banner', $storeId);
        $state->setName('show_welcome_banner');
        $state->setValue($type);
        $state->setStoreId($storeId);
        $this->stateRepository->save($state);
    }

    /**
     * Updates the signup request to be complete
     *
     * @param int $storeId
     *
     * @return void
     */
    private function completeSignup($storeId)
    {
        try {
            $state = $this->stateRepository->getByNameAndStore('signup_request', $storeId);
            if ($state->getId()) {
                $this->stateRepository->delete($state);
            }
        } catch (CouldNotDeleteException $e) {
            $this->logger->error('PureClarity: could not clear signup state. Error was: ' . $e->getMessage());
        }
    }

    /**
     * Triggers a run of all feeds
     *
     * @param int $storeId
     */
    private function triggerFeeds(int $storeId): void
    {
        $feeds = [
            'product',
            'category',
            'user',
            'orders'
        ];

        if ($storeId === 0) {
            $store = $this->storeManager->getDefaultStoreView();
            if ($store) {
                $storeId = (int)$store->getId();
            }
        }

        $this->feedRequest->requestFeeds($storeId, $feeds, true);
    }
}
