<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\AdminDataInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Sets an admin URL.
 */
class SetAdminUrl implements StepInterface
{
    /**
     * @var AdminDataInterface
     */
    private $adminData;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @param AdminDataInterface $adminData
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     */
    public function __construct(
        AdminDataInterface $adminData,
        ConfigWriter $configWriter,
        LoggerInterface $logger
    ) {
        $this->adminData = $adminData;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $adminUrl = $this->adminData->getUrl();

        if (!$adminUrl) {
            $this->logger->info('Not updating env.php backend front name. (ADMIN_URL not set)');

            return;
        }

        $this->logger->info('Updating env.php backend front name.');
        $config['backend']['frontName'] = $adminUrl;

        try {
            $this->configWriter->update($config);
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_ENV_PHP_IS_NOT_WRITABLE, $e);
        }
    }
}
