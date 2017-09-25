<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\DB\ConnectionInterface;
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
     * @var Environment
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

        $data = ['`email`' => $this->environment->getAdminEmail()];

        if ($this->environment->getAdminFirstname()) {
            $data['`firstname`'] = $this->environment->getAdminFirstname();
        }

        if ($this->environment->getAdminLastname()) {
            $data['`lastname`'] = $this->environment->getAdminFirstname();
        }

        if ($this->environment->getAdminUsername()) {
            $data['`username`'] = $this->environment->getAdminUsername();
        }

        if ($this->environment->getAdminPassword()) {
            $data['`password`'] = $this->passwordGenerator->generate(
                $this->environment->getAdminPassword()
            );
        }

        $fields = array_map(
            function ($key) {
                return $key . ' = ?';
            },
            array_keys($data)
        );
        $query = 'UPDATE `admin_user` SET ' . implode(', ', $fields) . ' WHERE `user_id` = 1';

        $this->connection->affectingQuery(
            $query,
            array_values($data)
        );
    }
}
