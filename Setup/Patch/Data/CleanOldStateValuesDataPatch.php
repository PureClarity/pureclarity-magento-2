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
use Pureclarity\Core\Setup\Service\CleanOldStateValuesService;

/**
* Cleans out old state values that are not used anymore.
*/
class CleanOldStateValuesDataPatch implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var CleanOldStateValuesService
     */
    private CleanOldStateValuesService $cleanOldStatesService;

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param CleanOldStateValuesService $cleanOldStatesService
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        CleanOldStateValuesService $cleanOldStatesService,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->cleanOldStatesService = $cleanOldStatesService;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Clean out old state values that are not used anymore.
     *
     * @return $this
     */
    public function apply(): CleanOldStateValuesDataPatch
    {
        $this->moduleDataSetup->startSetup();
        $this->cleanOldStatesService->execute();
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
        return [
            ConfigureFromPreviousInstallDataPatch::class
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion(): string
    {
        return '3.0.0';
    }
}
