<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;

/**
 * @inheritdoc
 */
class ComposerDumpAutoload implements StepInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param ShellInterface $shell
     */
    public function __construct(ShellInterface $shell)
    {
        $this->shell = $shell;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->shell->execute('composer dump-autoload -o --ansi --no-interaction');
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_COMPOSER_DUMP_AUTOLOAD_FAILED, $e);
        }
    }
}
