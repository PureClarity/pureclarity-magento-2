<?php
namespace PureClarity\DataPatch\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;

class AddPureClarityAttributes implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var EavSetupFactory */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
     
        $eavSetup = $this->eavSetupFactory->create(
            [
                'setup' => $this->moduleDataSetup
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

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '7.0.0';
    }
}