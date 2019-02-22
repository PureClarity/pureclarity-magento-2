<?php
namespace Pureclarity\Core\Plugin\Model;

use Magento\Store\Model\StoreManagerInterface;

class Config  {

    protected $_storeManager;

    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
    }


    public function afterGetAttributeUsedForSortByArray(\Magento\Catalog\Model\Config $catalogConfig, $options)
    {
        //Changing label
        $customOption['relevance'] = __( 'Relevance' );

        //Merge default sorting options with custom options
        $options = array_merge($customOption, $options);
     
        return $options;
    }
}