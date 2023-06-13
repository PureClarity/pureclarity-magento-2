<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Pureclarity\Core\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Pureclarity\Core\Setup\Service\ConfigureFromPreviousInstallService;

/**
* Populates data in the pureclarity_state table if already configured.
*/
class ConfigureFromPreviousInstallDataPatch implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ConfigureFromPreviousInstallService
     */
    private ConfigureFromPreviousInstallService $configureFromPreviousService;

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param ConfigureFromPreviousInstallService $configureFromPreviousService
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ConfigureFromPreviousInstallService $configureFromPreviousService,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->configureFromPreviousService = $configureFromPreviousService;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Populates data in the pureclarity_state table if already configured.
     *
     * @return $this
     */
    public function apply(): ConfigureFromPreviousInstallDataPatch
    {
        $this->moduleDataSetup->startSetup();
        $this->configureFromPreviousService->execute();
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
        return '3.0.0';
    }
}
