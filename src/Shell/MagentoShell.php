<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

use Symfony\Component\Process\Process;

/**
 * ./bin/magento shell wrapper.
 */
class MagentoShell implements ShellInterface
{
    /**
     * @var ShellProcess
     */
    private $shell;

    /**
     * @param ShellProcess $shell
     */
    public function __construct(ShellProcess $shell)
    {
        $this->shell = $shell;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $command, array $args = []): Process
    {
        return $this->shell->execute('php ./bin/magento ' . $command . ' --ansi --no-interaction', $args);
    }
}
