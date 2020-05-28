<?php

declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Filesystem\Flag;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Attempt to perform JS bundling using Baler.
 */
class RunBaler implements StepInterface
{
    /**
     * @var BuildInterface
     */
    private $buildConfig;

    /**
     * @var Flag\Manager
     */
    private $flagManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param LoggerInterface $logger,
     * @param BuildInterface $buildConfig,
     * @param Flag\Manager $flagManager,
     * @param ValidatorInterface $validator,
     * @param ShellInterface $shell
     */
    public function __construct(
        LoggerInterface $logger,
        BuildInterface $buildConfig,
        Flag\Manager $flagManager,
        ValidatorInterface $validator,
        ShellInterface $shell
    ) {
        $this->logger = $logger;
        $this->buildConfig = $buildConfig;
        $this->flagManager = $flagManager;
        $this->validator = $validator;
        $this->shell = $shell;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->buildConfig->get(BuildInterface::VAR_SCD_USE_BALER)) {
            $this->logger->debug('Baler JS bundling is disabled.');

            return;
        }

        if (!$this->flagManager->exists(Flag\Manager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)) {
            $this->logger->notice('Cannot run baler because static content has not been deployed.');

            return;
        }

        $result = $this->validator->validate();

        if ($result instanceof Result\Error) {
            $this->logger->warning($result->getError());

            foreach (explode(PHP_EOL, $result->getSuggestion()) as $detail) {
                $this->logger->warning(' - ' . $detail);
            }

            return;
        }

        $this->logger->info('Running Baler JS bundler.');

        try {
            $this->shell->execute('baler');
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_BALER_NOT_FOUND, $e);
        }

        $this->logger->info('Baler JS bundling complete.');
    }
}
