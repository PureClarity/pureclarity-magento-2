<?php
namespace Pureclarity\Core\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\CouldNotDeleteException;

class CleanSlate implements DataPatchInterface
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
        $this->moduleDataSetup->startSetup();
        $this->cleanState();
        $this->moduleDataSetup->endSetup();
    }

    /**
     * Cleans out old state values that are not used any more
     *
     * @return void
     */
    private function cleanState()
    {
        try {
            $configuredState = $this->stateRepository->getByNameAndStore('is_configured', 0);
            if ($configuredState->getId()) {
                $this->stateRepository->delete($configuredState);
            }

            $defaultStoreState = $this->stateRepository->getByNameAndStore('default_store', 0);
            if ($defaultStoreState->getId()) {
                $this->stateRepository->delete($defaultStoreState);
            }

            $signupState = $this->stateRepository->getByNameAndStore('signup_request', 0);
            if ($signupState->getId() && $signupState->getValue() === 'complete') {
                $this->stateRepository->delete($signupState);
            }
        } catch (CouldNotDeleteException $e) {
            $this->logger->error('PureClarity: could not delete old state on upgrade: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            Pureclarity\Core\Setup\Patch\Data\CheckForPreviousInstall::class
        ];
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