<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Wizard\Util;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides basic console formatting.
 */
class OutputFormatter
{
    /**
     * @param OutputInterface $output
     * @param string $message
     */
    public function writeItem(OutputInterface $output, string $message)
    {
        $output->writeln(' - ' . $message);
    }

    /**
     * @param OutputInterface $output
     * @param bool $status
     * @param string $message
     */
    public function writeResult(OutputInterface $output, bool $status, string $message)
    {
        $message = $status
            ? '<info>' . $message . '</info>'
            : '<error>' . $message . '</error>';

        $output->writeln($message);
    }
}
