<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Setup;
 
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Catalog\Model\Product\Attribute\Frontend\Image as FrontendImage;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Pureclarity\Core\Model\Attribute\Backend\Image;

/**
 * Class InstallData
 *
 * Installs attributes required for PureClarity.
 */
class InstallData implements InstallDataInterface
{
    /** @var EavSetupFactory $eavSetupFactory */
    private $eavSetupFactory;
 
    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Installs attributes required for PureClarity.
     *
     * @param ModuleDataSetupInterface $setup Module data setup interface
     * @param ModuleContextInterface $context Module context interface
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \ValidateException
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(
            [
                'setup' => $setup
            ]
        );

        /*
         * Magento 2 does not properly support category attributes until version 2.1 or above
         * so don't install them if on 2.0
         */

        $isMagento20 = defined("\\Magento\\Framework\\AppInterface::VERSION");

        if (! $isMagento20) {
 
            // Add PureClarity CATEGORY Attribute group
            $eavSetup->addAttributeGroup(
                Category::ENTITY,
                'Default',
                'PureClarity',
                1000
            );

            // Add attribute for override image for categories and brands
            $eavSetup->addAttribute(
                Category::ENTITY,
                'pureclarity_category_image',
                [
                    'group' => 'PureClarity',
                    'input' => 'image',
                    'type' => 'varchar',
                    'backend' => Image::class,
                    'label' => 'PureClarity image',
                    'visible' => 1,
                    'required' => 0,
                    'user_defined' => 1,
                    'sort_order' => 6,
                    'global' => ScopedAttributeInterface::SCOPE_STORE
                ]
            );

            // Add attribute for hiding product from recommenders
            $eavSetup->addAttribute(
                Category::ENTITY,
                'pureclarity_hide_from_feed',
                [
                    'group' => 'PureClarity',
                    'input' => 'boolean',
                    'type' => 'int',
                    'backend' => '',
                    'source' => Boolean::class,
                    'label' => 'Exclude from recommenders',
                    'visible' => 1,
                    'required' => 0,
                    'user_defined' => 1,
                    'default' => 0,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'visible_on_front' => true
                ]
            );
        }

        // Add PureClarity PRODUCT Attribute group
        $eavSetup->addAttributeGroup(
            Product::ENTITY,
            'Default',
            'PureClarity',
            1000
        );

        // Add attribute for Search Tags
        $eavSetup->addAttribute(
            Product::ENTITY,
            'pureclarity_search_tags',
            [
                'group' => 'PureClarity',
                'input' => 'text',
                'type' => 'text',
                'label' => 'Search tags',
                'backend' => '',
                'visible' => 1,
                'required' => 0,
                'user_defined' => 1,
                'default' => '',
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible_on_front' => true
            ]
        );

        // Add attribute for exluding product from recommenders
        $eavSetup->addAttribute(
            Product::ENTITY,
            'pureclarity_exc_rec',
            [
                'group' => 'PureClarity',
                'input' => 'boolean',
                'type' => 'int',
                'label' => 'Exclude from recommenders',
                'backend' => '',
                'source' => Boolean::class,
                'visible' => 1,
                'required' => 0,
                'user_defined' => 1,
                'default' => 0,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible_on_front' => true
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'pureclarity_newarrival',
            [
                'group' => 'PureClarity',
                'input' => 'boolean',
                'type' => 'int',
                'label' => 'New arrival',
                'backend' => '',
                'source' => Boolean::class,
                'visible' => 1,
                'required' => 0,
                'user_defined' => 1,
                'default' => 0,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible_on_front' => true
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'pureclarity_onoffer',
            [
                'group' => 'PureClarity',
                'input' => 'boolean',
                'type' => 'int',
                'label' => 'On offer',
                'backend' => '',
                'source' => Boolean::class,
                'visible' => 1,
                'required' => 0,
                'user_defined' => 1,
                'default' => 0,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible_on_front' => true
            ]
        );

        // Add option for Image Overlay
        $eavSetup->addAttribute(
            Product::ENTITY,
            'pureclarity_overlay_image',
            [
                'input' => 'media_image',
                'type' => 'varchar',
                'label' => 'PureClarity Overlay Image',
                'frontend' => FrontendImage::class,
                'visible' => 1,
                'required' => 0,
                'user_defined' => 1,
                'global' => ScopedAttributeInterface::SCOPE_STORE
            ]
        );
 
        $setup->endSetup();
    }
}
