<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Pureclarity\Core\Setup\Patch\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Pureclarity\Core\Setup\Service\AddPureClarityAttributesService;
use Zend_Validate_Exception;

/**
* Installs attributes required for PureClarity.
*/
class AddPureClarityAttributesDataPatch implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var AddPureClarityAttributesService
     */
    private AddPureClarityAttributesService $attributesInstallerService;

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param AddPureClarityAttributesService $attributesInstallerService
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        AddPureClarityAttributesService $attributesInstallerService,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->attributesInstallerService = $attributesInstallerService;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Install attributes required for PureClarity.
     *
     * @return $this
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function apply(): AddPureClarityAttributesDataPatch
    {
        $this->moduleDataSetup->startSetup();
        $this->attributesInstallerService->execute($this->moduleDataSetup);
        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion(): string
    {
        return '7.0.0';
    }
}
