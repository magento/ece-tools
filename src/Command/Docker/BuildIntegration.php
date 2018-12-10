<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Docker;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Docker\BuilderFactory;
use Magento\MagentoCloud\Docker\BuilderInterface;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Docker build for internal integration testing.
 */
class BuildIntegration extends Command
{
    const NAME = 'docker:build:integration';
    const ARGUMENT_PHP = 'php';
    const ARGUMENT_NGINX = 'nginx';
    const ARGUMENT_DB = 'db';

    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var File
     */
    private $file;

    /**
     * @var RepositoryFactory
     */
    private $configFactory;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param BuilderFactory $builderFactory
     * @param File $file
     * @param RepositoryFactory $configFactory
     * @param Environment $environment
     */
    public function __construct(
        BuilderFactory $builderFactory,
        File $file,
        RepositoryFactory $configFactory,
        Environment $environment
    ) {
        $this->builderFactory = $builderFactory;
        $this->file = $file;
        $this->configFactory = $configFactory;
        $this->environment = $environment;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Build test docker configuration')
            ->addArgument(
                self::ARGUMENT_PHP,
                InputArgument::REQUIRED,
                'PHP version'
            )->addArgument(
                self::ARGUMENT_DB,
                InputArgument::REQUIRED,
                'DB version'
            )->addArgument(
                self::ARGUMENT_NGINX,
                InputArgument::REQUIRED,
                'Nginx version'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws FileSystemException
     * @throws ConfigurationMismatchException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $builder = $this->builderFactory->create(BuilderFactory::BUILDER_TEST);
        $config = $this->configFactory->create();

        $map = [
            self::ARGUMENT_PHP => BuilderInterface::PHP_VERSION,
            self::ARGUMENT_DB => BuilderInterface::DB_VERSION,
            self::ARGUMENT_NGINX => BuilderInterface::NGINX_VERSION,
        ];

        array_walk($map, function ($key, $option) use ($config, $input) {
            $config->set($key, $input->getArgument($option));
        });

        $this->file->filePutContents(
            $builder->getConfigPath(),
            Yaml::dump($builder->build($config), 4, 2)
        );

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
