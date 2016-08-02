<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Environment;

/**
 * CLI command for build hook. Responsible for preparing the codebase before it's moved to the server.
 */
class Build extends Command
{

    /**
     * @var Environment
     */
    private $env;

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('magento-cloud:build')
            ->setDescription('Invokes set of steps to build source code for the Magento on the Magento Cloud');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->env = new Environment();
        $this->build();
    }

    private function build()
    {
        $this->env->log("Start build.");

        $this->applyPatches();
        $this->compileDI();
        $this->clearTemp();
        $this->env->execute('rm -rf app/etc/env.php');
        $this->env->execute('rm -rf app/etc/config.php');

        /**
         * Writable directories will be erased when the writable filesystem is mounted to them. This
         * step backs them up to /app/init/
         */
        $this->env->log("Copying writable directories to temp directory.");
        foreach ($this->env->writableDirs as $dir) {
            $this->env->execute(sprintf('mkdir -p ./init/%s', $dir));
            $this->env->execute(sprintf('/bin/bash -c "shopt -s dotglob; cp -R %s/* ./init/%s/"', $dir, $dir));
            $this->env->execute(sprintf('rm -rf %s', $dir));
            $this->env->execute(sprintf('mkdir %s', $dir));
        }
    }

    /**
     * Apply any existing patches
     */
    private function applyPatches()
    {
        $this->env->log("Patching Magento.");
        $this->env->execute('/usr/bin/php ' . Environment::MAGENTO_ROOT . 'vendor/magento/magento-cloud-configuration/patch.php');
    }

    private function compileDI()
    {
        $this->env->log("Run DI compilation");
        $this->env->execute('rm -rf var/generation/*');
        $this->env->execute('rm -rf var/di/*');
        $this->env->execute("cd bin/; /usr/bin/php ./magento module:enable --all");
        $this->env->execute("cd bin/; /usr/bin/php ./magento setup:di:compile");
    }

    /**
     * Clear content of temp directory
     */
    private function clearTemp()
    {
        $this->env->log("Clearing temporary directory.");
        $this->env->execute('rm -rf ../init/*');
    }
}
