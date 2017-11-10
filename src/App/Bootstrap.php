<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\Application;

/**
 * Class Bootstrap.
 *
 * @codeCoverageIgnore
 */
class Bootstrap
{
    /**
     * @var string
     */
    private $root;

    /**
     * @var array
     */
    private $server;

    const INIT_PARAM_DIRS_CONFIG = 'DIRS_CONFIG';

    /**
     * @param string $root
     * @param array $server
     */
    public function __construct(string $root, array $server)
    {
        $this->root = $root;
        $this->server = $server;
    }

    /**
     * @param string $root
     * @param array $server
     * @return Bootstrap
     */
    public static function create(string $root = ECE_BP, array $server = [])
    {
        $server = array_replace($_SERVER, $server);

        return new self($root, $server);
    }

    /**
     * @return Application
     */
    public function createApplication()
    {
        $config = $this->server[static::INIT_PARAM_DIRS_CONFIG] ?? [];
        $container = Container::getInstance($this->root, $config);


        return new Application($container);
    }
}
