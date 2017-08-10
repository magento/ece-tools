<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\Environment;
use Magento\MagentoCloud\Shell\ShellInterface;

class Adapter
{
    /**
     * @var ShellInterface
     */
    private $shell;

    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPassword;

    public function __construct(Environment $environment, ShellInterface $shell)
    {
        $this->shell = $shell;
        $relationships = $environment->getRelationships();

        $this->dbHost = $relationships["database"][0]["host"];
        $this->dbName = $relationships["database"][0]["path"];
        $this->dbUser = $relationships["database"][0]["username"];
        $this->dbPassword = $relationships["database"][0]["password"];
    }

    public function execute(string $query)
    {
        $password = strlen($this->dbPassword) ? sprintf('-p%s', $this->dbPassword) : '';

        return $this->shell->execute("mysql -u $this->dbUser -h $this->dbHost -e \"$query\" $password $this->dbName");
    }
}
