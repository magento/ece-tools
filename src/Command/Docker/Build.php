<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Docker;

use Magento\MagentoCloud\Docker\BuilderFactory;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Build extends Command
{
    const NAME = 'docker:build';
    const OPTION_PHP = 'php';
    const OPTION_NGINX = 'nginx';
    const OPTION_DB = 'db';

    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var File
     */
    private $file;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @param BuilderFactory $builderFactory
     * @param File $file
     * @param FileList $fileList
     */
    public function __construct(BuilderFactory $builderFactory, File $file, FileList $fileList)
    {
        $this->builderFactory = $builderFactory;
        $this->file = $file;
        $this->fileList = $fileList;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Build docker configuration')
            ->addOption(
                self::OPTION_PHP,
                null,
                InputOption::VALUE_OPTIONAL,
                'PHP version'
            )
            ->addOption(
                self::OPTION_NGINX,
                null,
                InputOption::VALUE_OPTIONAL,
                'Nginx version'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Magento\MagentoCloud\Docker\Exception
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $builder = $this->builderFactory->create();

        if ($input->hasOption(self::OPTION_PHP)) {
            $builder->setPhpVersion($input->getOption(self::OPTION_PHP));
        }

        if ($input->hasOption(self::OPTION_NGINX)) {
            $builder->setNginxVersion($input->getOption(self::OPTION_NGINX));
        }

        if ($input->hasOption(self::OPTION_DB)) {
            $builder->setDbVersion($input->getOption(self::OPTION_DB));
        }

        $configFile = $this->fileList->getDockerCompose();
        $config = Yaml::dump(
            $builder->build(),
            4,
            2
        );

        $this->file->filePutContents($configFile, $config);

        $output->writeln('<info>Configuration was built</info>');
    }
}
