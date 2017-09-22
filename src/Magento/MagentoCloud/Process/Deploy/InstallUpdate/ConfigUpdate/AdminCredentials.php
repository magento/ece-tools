<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\EnvironmentAdmin;
use Magento\MagentoCloud\Util\PasswordGenerator;

class AdminCredentials implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EnvironmentAdmin
     */
    private $environmentAdmin;

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
     * @param EnvironmentAdmin $environmentAdmin
     * @param PasswordGenerator $passwordGenerator
     */
    public function __construct(
        LoggerInterface $logger,
        ConnectionInterface $connection,
        EnvironmentAdmin $environmentAdmin,
        PasswordGenerator $passwordGenerator
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->environmentAdmin = $environmentAdmin;
        $this->passwordGenerator = $passwordGenerator;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /* Old query for reference:
        $query = 'UPDATE `admin_user` SET `firstname` = ?, `lastname` = ?, `email` = ?, `username` = ?, `password` = ?'
            . ' WHERE `user_id` = 1';
        */

        $parameters = [];
        $query = "";
        $addColumnValueToBeUpdated = function (&$query, &$parameters, $columnName, $value, $value2 = null) {
            if (empty($value)) {
                return;
            }
            if (!empty($query)) {
                $query .= ",";
            }
            $query .= " $columnName = ?";
            $parameters[] = $value2 ?? $value;
        };
        $addColumnValueToBeUpdated($query, $parameters, "`firstname`", $this->environmentAdmin->getAdminFirstname());
        $addColumnValueToBeUpdated($query, $parameters, "`lastname`", $this->environmentAdmin->getAdminLastname());
        $addColumnValueToBeUpdated($query, $parameters, "`email`", $this->environmentAdmin->getAdminEmail());
        $addColumnValueToBeUpdated($query, $parameters, "`username`", $this->environmentAdmin->getAdminUsername());
        $adminPassword = $this->environmentAdmin->getAdminPassword();
        $addColumnValueToBeUpdated($query, $parameters, "`password`", $adminPassword, empty($adminPassword) ? null : $this->passwordGenerator->generateSaltAndHash($adminPassword));
        if (empty($query)) {
            return;  // No variables set ; nothing to do
        }
        $this->logger->info('Updating admin credentials.');
        $query = "UPDATE `admin_user` SET" . $query . " WHERE `user_id` = 1";
        $this->connection->affectingQuery($query, $parameters);
    }
}
