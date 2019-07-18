<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

/**
 * ./bin/magento shell wrapper.
 */
class MagentoShell implements ShellInterface
{
    /**
     * @var Shell
     */
    private $shell;

    /**
     * @param Shell $shell
     */
    public function __construct(Shell $shell)
    {
        $this->shell = $shell;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $command, array $args = []): ProcessInterface
    {
        return $this->shell->execute('php ./bin/magento ' . $command . ' --ansi --no-interaction', array_filter($args));
    }
}
