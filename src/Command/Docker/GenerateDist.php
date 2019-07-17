<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Docker;

use Magento\MagentoCloud\Docker\Config\Dist\Generator;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates .dist files.
 */
class GenerateDist extends Command
{
    const NAME = 'docker:generate-dist';

    /**
     * @var Generator
     */
    private $distGenerator;

    /**
     * @param Generator $distGenerator
     */
    public function __construct(Generator $distGenerator)
    {
        $this->distGenerator = $distGenerator;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setAliases(['docker:config:convert'])
            ->setDescription('Generates Docker .dist files');
    }

    /**
     * {@inheritDoc}
     *
     * @throws FileSystemException
     * @throws ConfigurationMismatchException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->distGenerator->generate();

        $output->writeln('<info>Dist files generated</info>');
    }
}
