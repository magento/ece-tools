<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Check state at the beginning of the deployment
 */
class CheckState implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @param ConfigReader $configReader
     * @param FlagManager $flagManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConfigReader $configReader,
        FlagManager $flagManager,
        LoggerInterface $logger
    ) {
        $this->configReader = $configReader;
        $this->flagManager = $flagManager;
        $this->logger = $logger;
    }

    /**
     * Set flag if env.php does not exist
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $config = $this->configReader->read();

            //workaround when Magento creates empty env.php with one cache_type
            if (empty($config) || (count($config) == 1 && isset($config['cache_types']))) {
                $this->logger->info(sprintf('Set "%s" flag', FlagManager::FLAG_ENV_FILE_ABSENCE));
                $this->flagManager->set(FlagManager::FLAG_ENV_FILE_ABSENCE);
            }
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
