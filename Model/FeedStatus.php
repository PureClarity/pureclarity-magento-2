<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class FeedStatus
 *
 * Feed status checker model
 */
class FeedStatus
{
    /** @var mixed[] $feedStatusData */
    private $feedStatusData;

    /** @var mixed[] $progressData */
    private $progressData;

    /** @var mixed[] $requestedFeedData */
    private $requestedFeedData;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var Filesystem $fileSystem */
    private $fileSystem;

    /** @var Data $coreHelper */
    private $coreHelper;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var Json $json */
    private $json;

    /** @var TimezoneInterface $timezone */
    private $timezone;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param StateRepositoryInterface $stateRepository
     * @param Filesystem $fileSystem
     * @param Data $coreHelper
     * @param CoreConfig $coreConfig
     * @param Json $json
     * @param TimezoneInterface $timezone
     * @param LoggerInterface $logger
     */
    public function __construct(
        StateRepositoryInterface $stateRepository,
        Filesystem $fileSystem,
        Data $coreHelper,
        CoreConfig $coreConfig,
        Json $json,
        TimezoneInterface $timezone,
        LoggerInterface $logger
    ) {
        $this->stateRepository = $stateRepository;
        $this->fileSystem      = $fileSystem;
        $this->coreHelper      = $coreHelper;
        $this->coreConfig      = $coreConfig;
        $this->json            = $json;
        $this->timezone        = $timezone;
        $this->logger          = $logger;
    }

    /**
     * Returns whether any of the feed types provided are currently in progress
     *
     * @param string[] $types
     * @param integer $storeId
     *
     * @return bool
     */
    public function getAreFeedsInProgress($types, $storeId = 0)
    {
        $inProgress = false;
        foreach ($types as $type) {
            $status = $this->getFeedStatus($type, $storeId);
            if ($status['running'] === true) {
                $inProgress = true;
            }
        }

        return $inProgress;
    }

    /**
     * Returns the status of the product feed
     *
     * @param string $type
     * @param integer $storeId
     * @return mixed[]
     */
    public function getFeedStatus($type, $storeId = 0)
    {
        if (!isset($this->feedStatusData[$type])) {
            $status = [
                'enabled' => true,
                'running' => false,
                'class' => 'pc-feed-not-sent',
                'label' => __('Not Sent')
            ];

            if ($type === 'brand') {
                if ($this->coreConfig->isBrandFeedEnabled($storeId) === false) {
                    $status['enabled'] = false;
                    $status['label'] = __('Not Enabled');
                    $status['class'] = 'pc-feed-disabled';
                }
            }

            if ($status['enabled'] === true) {
                // check if it's been requested
                $requested = $this->hasFeedBeenRequested($type, $storeId);

                if ($requested) {
                    $status['running'] = true;
                    $status['label'] = __('Waiting for feed run to start');
                    $status['class'] = 'pc-feed-waiting';
                }

                // check if it's been requested
                $requested = $this->isFeedWaiting($type, $storeId);

                if ($requested) {
                    $status['running'] = true;
                    $status['label'] = __('Waiting for other feeds to finish');
                    $status['class'] = 'pc-feed-waiting';
                }

                if ($status['running']) {
                    // check if it's in progress
                    $progress = $this->feedProgress($type);
                    if ($progress !== false) {
                        $status['running'] = true;
                        $status['label'] = __('In progress: %1%', $progress);
                        $status['class'] = 'pc-feed-in-progress';
                    }
                }

                if ($status['running'] !== true) {
                    // check it's last run date
                    $state = $this->stateRepository->getByNameAndStore('last_' . $type . '_feed_date', $storeId);
                    $lastProductFeedDate = ($state->getId() !== null) ? $state->getValue() : '';
                    if ($lastProductFeedDate) {
                        $status['label'] = __(
                            'Last sent %1',
                            $this->timezone->formatDate(
                                $lastProductFeedDate,
                                \IntlDateFormatter::SHORT,
                                true
                            )
                        );
                        $status['class'] = 'pc-feed-complete';
                    }
                }
            }

            $this->feedStatusData[$type] = $status;
        }

        return $this->feedStatusData[$type];
    }

    /**
     * @param string $feedType
     * @param integer $storeId
     * @return bool
     */
    private function hasFeedBeenRequested($feedType, $storeId)
    {
        $requested = false;
        $scheduleData = $this->getScheduledFeedData();

        if (!empty($scheduleData) && (int)$scheduleData['store'] === (int)$storeId) {
            $requested = in_array($feedType, $scheduleData['feeds']);
        }

        return $requested;
    }

    /**
     * @param string $feedType
     * @param integer $storeId
     * @return bool
     */
    private function isFeedWaiting($feedType, $storeId)
    {
        $waiting = false;
        $state = $this->stateRepository->getByNameAndStore('running_feeds', $storeId);
        $waitingFeedsRaw = ($state->getId() !== null) ? $state->getValue() : '';

        if ($waitingFeedsRaw) {
            $waitingFeeds = $this->json->unserialize($waitingFeedsRaw);
            $waiting = in_array($feedType, $waitingFeeds);
        }

        return $waiting;
    }

    /**
     * @param string $feedType
     * @return bool|float
     */
    private function feedProgress($feedType)
    {
        $inProgress = false;
        $progressData = $this->getProgressData();

        if (!empty($progressData) && $progressData['name'] === $feedType) {
            $inProgress = round(($progressData['cur'] / $progressData['max']) * 100);
        }

        return $inProgress;
    }

    /**
     * @return bool
     */
    private function getProgressData()
    {
        if ($this->progressData === null) {
            $this->progressData = [];
            $progressFileName = $this->coreHelper->getProgressFileName();
            /** @var ReadInterface $fileReader */
            $fileReader = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);

            if ($fileReader->isExist($progressFileName)) {
                try {
                    $progressData = $fileReader->readFile($progressFileName);
                    $this->progressData = $this->json->unserialize($progressData);
                } catch (FileSystemException $e) {
                    $this->logger->error('Could not get PureClarity feed progress data: ' . $e->getMessage());
                }
            }
        }

        return $this->progressData;
    }

    /**
     * @return bool
     */
    private function getScheduledFeedData()
    {
        if ($this->requestedFeedData === null) {
            $this->requestedFeedData = [];
            $scheduleFile = $this->coreHelper->getPureClarityBaseDir() . DIRECTORY_SEPARATOR . 'scheduled_feed';
            /** @var ReadInterface $fileReader */
            $fileReader = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);
            if ($fileReader->isExist($scheduleFile)) {
                try {
                    $scheduledData = $fileReader->readFile($scheduleFile);
                    $this->requestedFeedData = $this->json->unserialize($scheduledData);
                } catch (FileSystemException $e) {
                    $this->logger->error('Could not get PureClarity schedule data: ' . $e->getMessage());
                }
            }
        }

        return $this->requestedFeedData;
    }
}
