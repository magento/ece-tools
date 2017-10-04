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
        $data = [];

        $adminEmail = $this->environment->getAdminEmail();
        if ($adminEmail && !$this->isEmailUsed($adminEmail)) {
            $data['`email`'] = $adminEmail;
        }

        $adminUserName = $this->environment->getAdminUsername();
        if ($adminUserName && !$this->isUsernameUsed($adminUserName)) {
            $data['`username`'] = $adminUserName;
        }

        $adminFirstName = $this->environment->getAdminFirstname();
        if ($adminFirstName) {
            $data['`firstname`'] = $adminFirstName;
        }

        $adminLastName = $this->environment->getAdminLastname();
        if ($adminLastName) {
            $data['`lastname`'] = $adminLastName;
        }

        $adminPassword = $this->environment->getAdminPassword();
        if ($adminPassword) {
            $data['`password`'] = $this->passwordGenerator->generateSaltAndHash($adminPassword);
        }

        if (!$data) {
            $this->logger->info('Updating admin credentials: nothing to update.');
            return;
        }

        $this->logger->info('Updating admin credentials.');

        $fields = array_map(
            function ($key) {
                return $key . ' = ?';
            },
            array_keys($data)
        );
            $query = 'UPDATE `admin_user` SET ' . implode(', ', $fields) . ' ORDER BY `user_id` ASC LIMIT 1';

        $this->connection->affectingQuery(
            $query,
            array_values($data)
        );
    }

    /**
     * @param string $email
     * @return bool
     */
    private function isEmailUsed(string $email): bool
    {
        $isUsed = count($this->connection->select('SELECT 1 FROM `admin_user` WHERE `email` = ?', [$email])) > 0;

        if ($isUsed) {
            $this->logger->info('Some administrator already uses this email ' . $email);
        }

        return $isUsed;
    }

    /**
     * @param string $username
     * @return bool
     */
    private function isUsernameUsed(string $username): bool
    {
        $isUsed = count($this->connection->select('SELECT 1 FROM `admin_user` WHERE `username` = ?', [$username])) > 0;

        if ($isUsed) {
            $this->logger->info('Some administrator already uses this username ' . $username);
        }

        return $isUsed;
    }
}
