<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\Magento\Shared\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Shared\WriterInterface;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;

/**
 * Performs module management operations.
 */
class Module
{
    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @param ReaderInterface $reader
     * @param WriterInterface $writer
     * @param BuildInterface $stageConfig
     * @param ShellFactory $shellFactory
     */
    public function __construct(
        ReaderInterface $reader,
        WriterInterface $writer,
        BuildInterface $stageConfig,
        ShellFactory $shellFactory
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->stageConfig = $stageConfig;
        $this->magentoShell = $shellFactory->createMagento();
    }

    /**
     * Reconciling installed modules with shared config.
     * Returns list of new enabled modules or an empty array if no modules were enabled.
     *
     * @throws ShellException
     * @throws FileSystemException
     * @throws ConfigException
     */
    public function refresh(): array
    {
        // Update initial config file to avoid broken file error.
        $this->writer->update(['modules' => []]);

        $moduleConfig = $this->reader->read()['modules'] ?? [];

        $this->magentoShell->execute(
            'module:enable --all',
            [$this->stageConfig->get(BuildInterface::VAR_VERBOSE_COMMANDS)]
        );

        $updatedModuleConfig = $this->reader->read()['modules'] ?? [];

        if ($moduleConfig) {
            $this->writer->update(['modules' => $moduleConfig]);
        }

        return array_keys(array_diff_key($updatedModuleConfig, $moduleConfig));
    }
}
