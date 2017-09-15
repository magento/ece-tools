<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Util\PasswordGenerator;

class AdminCredentials implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EnvPhp
     */
    private $environment;

    /**
     * @var PasswordGenerator
     */
    private $passwordGenerator;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @param LoggerInterface $logger
     * @param ConnectionInterface $connection
     * @param Environment $environment
     * @param PasswordGenerator $passwordGenerator
     */
    public function __construct(
        LoggerInterface $logger,
        ConnectionInterface $connection,
        Environment $environment,
        PasswordGenerator $passwordGenerator
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->environment = $environment;
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

        $query = 'UPDATE `admin_user` SET `firstname` = ?, `lastname` = ?, `email` = ?, `username` = ?, `password` = ?'
            . ' WHERE `user_id` = 1';

        $this->connection->affectingQuery(
            $query,
            [
                $this->environment->getAdminFirstname(),
                $this->environment->getAdminLastname(),
                $this->environment->getAdminEmail(),
                $this->environment->getAdminUsername(),
                $password,
            ]
        );
    }
}
