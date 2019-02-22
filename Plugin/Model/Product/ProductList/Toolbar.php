<?php
namespace Pureclarity\Core\Plugin\Model\Product\ProductList;


class Toolbar 
{
    public function __construct(
        \Magento\Framework\App\Request\Http $request
    ) { }  
    
    // Overwrites magentos sort by logic to use our order.
    public function afterSetCollection(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $toolbar,
        $interceptor,
        $collection        
    ) {
        return $collection;
    }

    // Sets the default sort by value to the new relevance sort by to match general search
    public function beforeGetCurrentOrder(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $toolbar
    ) {
        $toolbar->setDefaultOrder('relevance');      
    }

    // Sets the default sort by direction to descending
    public function beforeGetCurrentDirection(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $toolbar
    ) {
        $toolbar->setDefaultDirection('desc');      
    }
}