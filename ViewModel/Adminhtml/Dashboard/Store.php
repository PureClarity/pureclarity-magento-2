<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard;

use Pureclarity\Core\Helper\StoreData;

/**
 * Class Store
 *
 * Store ViewModel for Dashboard page
 */
class Store
{
    /** @var StoreData $storeData */
    private $storeData;

    /**
     * @param StoreData $storeData
     */
    public function __construct(
        StoreData $storeData
    ) {
        $this->storeData  = $storeData;
    }

    /**
     * Gets list of stores for display
     *
     * @return string
     */
    public function getStoreURL($storeId)
    {
        return $this->storeData->getStoreURL($storeId);
    }

    /**
     * Gets list of stores for display
     *
     * @return string
     */
    public function getStoreCurrency($storeId)
    {
        return $this->storeData->getStoreCurrency($storeId);
    }

    /**
     * Gets list of stores for display
     *
     * @return integer
     */
    public function getStoreTimezone($storeId)
    {
        return $this->storeData->getStoreTimezone($storeId);
    }
}
