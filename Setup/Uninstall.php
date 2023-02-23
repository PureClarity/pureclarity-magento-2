<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Pureclarity\Core\Model\Zones\Uninstaller;

/**
 * Class Uninstall
 *
 * Removes schema changes from database
 */
class Uninstall implements UninstallInterface
{
    /** @var Uninstaller $uninstaller */
    private $uninstaller;

    /**
     * Constructor to inject dependencies into class.
     *
     * @param Uninstaller $uninstaller
     */
    public function __construct(Uninstaller $uninstaller)
    {
        $this->uninstaller = $uninstaller;
    }

    /**
     * Uninstalls PureClarity.
     *
     * @param SchemaSetupInterface $setup   Schema setup interface.
     * @param ModuleContextInterface $context Module context interface.
     *
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();
        
        $connection = $setup->getConnection();
        $connection->dropTable($setup->getTable('pureclarity_productfeed'));
        $connection->dropTable($setup->getTable('pureclarity_state'));

        $this->uninstaller->uninstall();

        $setup->endSetup();
    }
}
