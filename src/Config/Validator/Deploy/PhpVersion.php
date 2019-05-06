<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Composer\Composer;
use Composer\Package\Version\VersionParser;
use Composer\Semver\Constraint\ConstraintInterface;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Package\MagentoVersion;
use Psr\Log\LoggerInterface;

/**
 * Checks the current PHP version in accordance with
 * the maximum allowed constraint of PHP of the magento/magento2-base package
 */
class PhpVersion implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var VersionParser
     */
    private $versionParser;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GlobalSection
     */
    private $globalSection;

    /**
     * @param Composer $composer
     * @param Validator\ResultFactory $resultFactory
     * @param VersionParser $versionParser
     * @param MagentoVersion $magentoVersion
     * @param LoggerInterface $logger
     * @param GlobalSection $globalSection
     */
    public function __construct(
        Composer $composer,
        Validator\ResultFactory $resultFactory,
        VersionParser $versionParser,
        MagentoVersion $magentoVersion,
        LoggerInterface $logger,
        GlobalSection $globalSection
    ) {
        $this->composer = $composer;
        $this->resultFactory = $resultFactory;
        $this->versionParser = $versionParser;
        $this->magentoVersion = $magentoVersion;
        $this->logger = $logger;
        $this->globalSection = $globalSection;
    }

    /**
     * Validates PHP version
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            if ($this->globalSection->get(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)) {
                return $this->resultFactory->success();
            }

            $recommendedPhpConstraint = $this->getRecommendedPhpConstraint();
            $currentPhpConstraint = $this->getCurrentPhpConstraint();

            if (!$recommendedPhpConstraint->matches($currentPhpConstraint)) {
                return $this->resultFactory->error(
                    sprintf(
                        'For Magento %s recommended PHP version satisfying the constraint %s. '
                        . 'Currently installed PHP version %s',
                        $this->magentoVersion->getVersion(),
                        $recommendedPhpConstraint->getPrettyString(),
                        $currentPhpConstraint->getPrettyString()
                    ),
                    "Change the version of PHP to the version that satisfies the restriction conditions.\n"
                    . 'Change the PHP version in the .magento.app.yaml file or create a support ticket'
                );
            }
        } catch (\Exception $e) {
            $this->logger->warning('Can\'t validate version of PHP: ' . $e->getMessage());
        }
        return $this->resultFactory->success();
    }

    /**
     * Returns the latest PHP constraint
     *
     * @return ConstraintInterface
     * @throws \Exception
     */
    private function getRecommendedPhpConstraint(): ConstraintInterface
    {
        $constraintString = $this->composer
            ->getLocker()
            ->getLockedRepository()
            ->findPackage('magento/magento2-base', '*')
            ->getRequires()['php']
            ->getConstraint()
            ->getPrettyString();

        $listOfConstraint = explode('|', $constraintString);
        $lastConstraint = end($listOfConstraint);

        return $this->versionParser->parseConstraints($lastConstraint);
    }

    /**
     * Returns the current PHP constraint
     *
     * @return ConstraintInterface
     * @throws \Exception
     */
    private function getCurrentPhpConstraint(): ConstraintInterface
    {
        return $this->versionParser->parseConstraints(preg_replace('#^([^~+-]+).*$#', '$1', PHP_VERSION));
    }
}
