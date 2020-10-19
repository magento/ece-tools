<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Config\Module;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Shell\ShellException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Performs module:refresh command
 *
 * @api
 */
class ModuleRefresh extends Command
{
    public const NAME = 'module:refresh';

    /**
     * @var Module
     */
    private $module;

    /**
     * @param Module $module
     */
    public function __construct(Module $module)
    {
        $this->module = $module;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Refreshes the configuration to enable newly added modules.');
    }

    /**
     * {@inheritDoc}
     *
     * @throws ShellException
     * @throws FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $enabledModules = $this->module->refresh();

        $output->writeln(
            $enabledModules ?
                'The following modules have been enabled:' . PHP_EOL . implode(PHP_EOL, $enabledModules) :
                'No modules were changed.'
        );
    }
}
