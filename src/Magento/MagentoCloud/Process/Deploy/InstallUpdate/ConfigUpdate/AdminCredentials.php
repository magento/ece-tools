<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Magento\MagentoCloud\DB\Adapter;

class AdminCredentials implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var PasswordGenerator
     */
    private $passwordGenerator;

    /**
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param Adapter $adapter
     * @param PasswordGenerator $passwordGenerator
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        Adapter $adapter,
        PasswordGenerator $passwordGenerator
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->adapter = $adapter;
        $this->passwordGenerator = $passwordGenerator;
    }
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Updating admin credentials.');

        $password = $this->passwordGenerator->generate(
            $this->environment->getAdminPassword()
        );

        $query = sprintf(
            "update admin_user set firstname = '%s', lastname = '%s', email = '%s', username = '%s', password='%s'" .
            " where user_id = '1';",
            $this->environment->getAdminFirstname(),
            $this->environment->getAdminLastname(),
            $this->environment->getAdminEmail(),
            $this->environment->getAdminUsername(),
            $password
        );

        $this->adapter->execute($query);
    }
}
