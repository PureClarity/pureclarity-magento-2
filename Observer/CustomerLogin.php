<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;

class CustomerLogin implements ObserverInterface
{
    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->customerSession->setPureclarityTriggerLogin(true);
    }
}
