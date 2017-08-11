<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Shell\ShellInterface;

class Adapter
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment $environment
     * @param ShellInterface $shell
     */
    public function __construct(Environment $environment, ShellInterface $shell)
    {
        $this->shell = $shell;
        $this->environment = $environment;
    }

    public function execute(string $query)
    {
        $relationships = $this->environment->getRelationships();

        $dbHost = $relationships['database'][0]['host'];
        $dbName = $relationships['database'][0]['path'];
        $dbUser = $relationships['database'][0]['username'];
        $dbPassword = $relationships['database'][0]['password'];
        $password = strlen($dbPassword) ? sprintf('-p%s', $dbPassword) : '';

        return $this->shell->execute(
            "mysql -u $dbUser -h $dbHost -e \"$query\" $password $dbName"
        );
    }
}
