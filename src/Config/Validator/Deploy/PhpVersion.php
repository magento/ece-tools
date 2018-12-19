<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Composer\Package\Version\VersionParser;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\MultiConstraint;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\Manager as PackageManager;
use Magento\MagentoCloud\Config\Validator\Deploy\PhpVersion\ConstraintFactory;
use Psr\Log\LoggerInterface;
use Composer\Semver\Semver;

/**
 * Checks the current PHP version in accordance with
 * the maximum allowed constraint of  PHP of the magento/magento2-base package
 */
class PhpVersion implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @var VersionParser
     */
    private $versionParser;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var ConstraintFactory
     */
    private $constraintFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param PackageManager $packageManager
     * @param VersionParser $versionParser
     * @param MagentoVersion $magentoVersion
     * @param ConstraintFactory $constraintFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        PackageManager $packageManager,
        VersionParser $versionParser,
        MagentoVersion $magentoVersion,
        ConstraintFactory $constraintFactory,
        LoggerInterface $logger
    ) {
        $this->resultFactory = $resultFactory;
        $this->packageManager = $packageManager;
        $this->versionParser = $versionParser;
        $this->magentoVersion = $magentoVersion;
        $this->constraintFactory = $constraintFactory;
        $this->logger = $logger;
    }

    /**
     * Validates PHP version
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            $currentPhpConstraint = $this->getCurrentPhpConstraint();
            $recommendedPhpConstraint = $this->getRecommendedPhpConstraint();
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
        $requirePackages = $this->packageManager->get('magento/magento2-base')->getRequires();
        $phpConstraint = $requirePackages['php']->getConstraint();
        if (!$phpConstraint instanceof MultiConstraint) {
            return $phpConstraint;
        }
        $phpConstraints = [];
        $this->getAllConstraints($phpConstraint, $phpConstraints);
        $phpVersions = Semver::rsort(array_keys($phpConstraints));
        $higherPhpVersion = $phpVersions[0];
        /** @var ConstraintInterface $higherPhpConstraint */
        $higherPhpConstraint = $phpConstraints[$higherPhpVersion];
        $bestCandidatePhpConstraint = $this->versionParser->parseConstraints($higherPhpVersion);
        if ($higherPhpConstraint->matches($bestCandidatePhpConstraint)) {
            return $bestCandidatePhpConstraint;
        }
        preg_match('/(\d*)\.(\d*)\.(\d*)/', $higherPhpVersion, $phpVersionParts);
        unset($phpVersionParts[0]);
        for ($i = 3; $i > 0; $i--) {
            if ($phpVersionParts[$i] < 1) {
                continue;
            }
            $phpVersionParts[$i]--;
            $bestCandidatePhpVersion = implode('.', $phpVersionParts);
            $bestCandidatePhpConstraint = $this->versionParser->parseConstraints($bestCandidatePhpVersion);
            if ($higherPhpConstraint->matches($bestCandidatePhpConstraint)) {
                break;
            }
            $phpVersionParts[$i] = 0;
        }
        /** @var ConstraintInterface $phpLowerConstraint */
        $phpLowerConstraint = $phpConstraints[$phpVersions[1]];
        if (!$phpLowerConstraint->matches($bestCandidatePhpConstraint)) {
            return $this->constraintFactory->multiconstraint([$phpLowerConstraint, $higherPhpConstraint]);
        }
        return $this->constraintFactory->multiconstraint([
            $this->constraintFactory->constraint('>=', $bestCandidatePhpConstraint->getPrettyString()),
            $higherPhpConstraint
        ]);
    }

    /**
     * Fills the variable $constraintList with an array consisting of Constraint objects
     *
     * @param ConstraintInterface $constraint
     * @param array $constraintList
     * @return void
     */
    private function getAllConstraints(ConstraintInterface $constraint, array &$constraintList)
    {
        if ($constraint instanceof MultiConstraint) {
            foreach ($constraint->getConstraints() as $item) {
                $this->getAllConstraints($item, $constraintList);
            }
        } else {
            preg_match('/\d*\.\d*\.\d*/', $constraint->getPrettyString(), $constraintParts);
            $constraintList[$constraintParts[0]] = $constraint;
        }
    }

    /**
     * Returns the current PHP constraint
     *
     * @return ConstraintInterface
     */
    private function getCurrentPhpConstraint(): ConstraintInterface
    {
        return $this->versionParser->parseConstraints(PHP_VERSION);
    }
}
