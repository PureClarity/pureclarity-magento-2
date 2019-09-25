<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\ResourceModel\State\Collection;
use Pureclarity\Core\Model\ResourceModel\StateFactory;
use Pureclarity\Core\Model\ResourceModel\State\CollectionFactory;
use Pureclarity\Core\Api\Data\StateSearchResultInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterfaceFactory;
use Pureclarity\Core\Api\Data\StateInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;

class StateRepository implements StateRepositoryInterface
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var SearchCriteriaInterfaceFactory */
    private $searchCriteriaFactory;

    /** @var CollectionProcessorInterface */
    private $collectionProcessor;
    
    /** @var StateFactory */
    private $stateFactory;

    /** @var StateSearchResultInterfaceFactory */
    private $stateSearchResultFactory;

    /**
     * @param CollectionFactory $collectionFactory
     * @param SearchCriteriaInterfaceFactory $searchCriteriaFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param StateFactory $stateFactory
     * @param StateSearchResultInterfaceFactory $stateSearchResultFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        SearchCriteriaInterfaceFactory $searchCriteriaFactory,
        CollectionProcessorInterface $collectionProcessor,
        StateFactory $stateFactory,
        StateSearchResultInterfaceFactory $stateSearchResultFactory
    ) {
        $this->collectionFactory         = $collectionFactory;
        $this->searchCriteriaFactory     = $searchCriteriaFactory;
        $this->collectionProcessor       = $collectionProcessor;
        $this->stateFactory              = $stateFactory;
        $this->stateSearchResultFactory  = $stateSearchResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function getByName($name)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        $collection->addFieldToSelect(StateInterface::ID);
        $collection->addFieldToSelect(StateInterface::NAME);
        $collection->addFieldToSelect(StateInterface::VALUE);
        $collection->addFieldToFilter(StateInterface::NAME, $name);

        $searchCriteria = $this->searchCriteriaFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $collection->setPageSize(1);
        $collection->load();

        return $collection->getFirstItem();
    }

    /**
     * @inheritdoc
     */
    public function getList($searchCriteria)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        $collection->addFieldToSelect(StateInterface::ID);
        $collection->addFieldToSelect(StateInterface::NAME);
        $collection->addFieldToSelect(StateInterface::VALUE);

        $this->collectionProcessor->process($searchCriteria, $collection);

        $collection->load();

        $searchResult = $this->stateSearchResultFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }

    /**
     * @inheritdoc
     */
    public function save($state)
    {
        try {
            $resource = $this->stateFactory->create();
            $resource->save($state);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the State: %1',
                $exception->getMessage()
            ));
        }

        return $state;
    }

    /**
     * @inheritdoc
     */
    public function delete($state)
    {
        try {
            $resource = $this->stateFactory->create();
            $resource->delete($state);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Unable to remove State %1',
                $exception->getMessage()
            ));
        }

        return true;
    }
}