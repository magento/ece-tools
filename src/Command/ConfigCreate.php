<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Creates .magento.env.yaml
 */
class ConfigCreate extends Command
{
    const NAME = 'cloud:config:create';

    const ARG_CONFIGURATION = 'configuration';

    /**
     * @var ConfigFileList
     */
    private $configFileList;

    /**
     * @var File
     */
    private $file;

    /**
     * @param ConfigFileList $configFileList
     * @param File $file
     */
    public function __construct(ConfigFileList $configFileList, File $file)
    {
        $this->configFileList = $configFileList;
        $this->file = $file;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription(
                'Creates a `.magento.env.yaml` file with the specified build, deploy, and post-deploy variable ' .
                'configuration. Overwrites any existing `.magento,.env.yaml` file.'
            )
            ->addArgument(
                self::ARG_CONFIGURATION,
                InputArgument::REQUIRED,
                'Configuration in JSON format'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $input->getArgument(self::ARG_CONFIGURATION);

        $decodedConfig = json_decode($configuration, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Wrong JSON format: ' . json_last_error_msg());
        }

        $yaml = Yaml::dump($decodedConfig, 10, 2);
        $filePath = $this->configFileList->getEnvConfig();

        $this->file->filePutContents($filePath, $yaml);

        $output->writeln(sprintf("Config file %s was created", $filePath));
    }
}
