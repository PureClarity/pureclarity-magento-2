<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Pureclarity\Core\Setup\Service;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Frontend\Image as FrontendImage;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Pureclarity\Core\Model\Attribute\Backend\Image;
use Magento\Framework\Validator\ValidateException;

/**
 * Installs attributes required for PureClarity.
 */
class AddPureClarityAttributesService
{
    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Install attributes required for PureClarity.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     *
     * @return void
     * @throws LocalizedException
     * @throws ValidateException
     */
    public function execute(ModuleDataSetupInterface $moduleDataSetup): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $moduleDataSetup]);

        // Add PureClarity CATEGORY Attribute group.
        $eavSetup->addAttributeGroup(
            Category::ENTITY,
            'Default',
            'PureClarity',
            1000
        );
        $pureClarityCategoryImage = $eavSetup->getAttribute(Category::ENTITY, 'pureclarity_category_image');

        if (!$pureClarityCategoryImage) {
            // Add attribute for override image for categories and brands.
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
        }
        $pureClarityHideFromFeed = $eavSetup->getAttribute(Category::ENTITY, 'pureclarity_hide_from_feed');

        if (!$pureClarityHideFromFeed) {
            // Add attribute for hiding product from recommenders.
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
        $pureClaritySearchTags = $eavSetup->getAttribute(Product::ENTITY, 'pureclarity_search_tags');

        if (!$pureClaritySearchTags) {
            // Add attribute for Search Tags.
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
        }
        $pureClarityExcRec = $eavSetup->getAttribute(Product::ENTITY, 'pureclarity_exc_rec');

        if (!$pureClarityExcRec) {
            // Add attribute for excluding product from recommenders.
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
        }
        $pureClarityNewArrival = $eavSetup->getAttribute(Product::ENTITY, 'pureclarity_newarrival');

        if (!$pureClarityNewArrival) {
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
        }
        $pureClarityOnOffer = $eavSetup->getAttribute(Product::ENTITY, 'pureclarity_onoffer');

        if (!$pureClarityOnOffer) {
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
        }
        $pureClarityOverlayImage = $eavSetup->getAttribute(Product::ENTITY, 'pureclarity_overlay_image');

        if (!$pureClarityOverlayImage) {
            // Add option for Image Overlay.
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
        }
    }
}
