<?php
namespace Pureclarity\Core\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class CheckForPreviousInstall implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var CoreConfig */
    private $coreConfig;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CoreConfig $coreConfig
     * @param StateRepositoryInterface $stateRepository
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CoreConfig $coreConfig,
        StateRepositoryInterface $stateRepository,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->coreConfig = $coreConfig;
        $this->stateRepository = $stateRepository;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {

        $this->moduleDataSetup->getConnection()->startSetup();
        $this->checkForPreviousInstall();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Populates data in the pureclarity_state table if already configured
     *
     * @return void
     */
    private function checkForPreviousInstall()
    {
        $stores = $this->storeManager->getStores();
        $configured = false;
        $configuredStoreId = null;
        foreach ($stores as $store) {
            $accessKey = $this->coreConfig->getAccessKey($store->getId());
            if ($accessKey) {
                $configured = true;
                $configuredStoreId = $store->getId();
                break;
            }
        }

        if ($configured && $configuredStoreId !== null) {
            try {
                $configuredState = $this->stateRepository->getByNameAndStore('is_configured', 0);
                $configuredState->setName('is_configured');
                $configuredState->setValue(1);
                $configuredState->setStoreId(0);
                $this->stateRepository->save($configuredState);

                $defaultStoreState = $this->stateRepository->getByNameAndStore('default_store', 0);
                $defaultStoreState->setName('default_store');
                $defaultStoreState->setValue($configuredStoreId);
                $defaultStoreState->setStoreId(0);
                $this->stateRepository->save($defaultStoreState);
            } catch (CouldNotSaveException $e) {
                $this->logger->error('PureClarity: could not set state on upgrade: ' . $e->getMessage());
            }

        }
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
        return '3.0.0';
    }
}