<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Docker;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Docker\BuilderFactory;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Builds Docker configuration for Magento project.
 */
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
     * @var Environment
     */
    private $environment;

    /**
     * @param BuilderFactory $builderFactory
     * @param File $file
     * @param FileList $fileList
     * @param Environment $environment
     */
    public function __construct(
        BuilderFactory $builderFactory,
        File $file,
        FileList $fileList,
        Environment $environment
    ) {
        $this->builderFactory = $builderFactory;
        $this->file = $file;
        $this->fileList = $fileList;
        $this->environment = $environment;

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
            )->addOption(
                self::OPTION_NGINX,
                null,
                InputOption::VALUE_OPTIONAL,
                'Nginx version'
            )->addOption(
                self::OPTION_DB,
                null,
                InputOption::VALUE_OPTIONAL,
                'DB version'
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

        if ($phpVersion = $input->getOption(self::OPTION_PHP)) {
            $builder->setPhpVersion($phpVersion);
        }

        if ($nginxVersion = $input->getOption(self::OPTION_NGINX)) {
            $builder->setNginxVersion($nginxVersion);
        }

        if ($dbVersion = $input->getOption(self::OPTION_DB)) {
            $builder->setDbVersion($dbVersion);
        }

        $configFile = $this->fileList->getMagentoDockerCompose();
        $config = Yaml::dump($builder->build(), 4, 2);

        $this->file->filePutContents($configFile, $config);

        $output->writeln('<info>Configuration was built</info>');
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        return !$this->environment->isMasterBranch();
    }
}
