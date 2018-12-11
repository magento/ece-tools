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
 * Validates PHP version
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
            $currentPhpConstraint = $this->constraintFactory->getCurrentPhpConstraint();
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
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException|\Exception
     */
    private function getRecommendedPhpConstraint(): ConstraintInterface
    {
        $requirePackages = $this->packageManager->get('magento/magento2-base')->getRequires();
        $phpConstraint = $requirePackages['php']->getConstraint();
        $phpConstraintList = [];
        $this->getAllConstraints($phpConstraint, $phpConstraintList);
        $versionList = Semver::rsort(array_keys($phpConstraintList));
        $higherPhpVersion = $versionList[0];
        /** @var ConstraintInterface $higherPhpConstraint */
        $higherPhpConstraint = $phpConstraintList[$higherPhpVersion];
        $higherConstraintParts = $this->getConstraintParts($higherPhpVersion);
        $i = 3;
        $newConstraintParts = $higherConstraintParts;
        while ($i >= 0) {
            $newConstraint = $this->versionParser->parseConstraints(implode('.', $newConstraintParts));
            if ($higherPhpConstraint->matches($newConstraint)) {
                break;
            }
            if ($newConstraintParts[$i]) {
                $newConstraintParts[$i] = 0;
            }
            $i--;
            if ($newConstraintParts[$i] >= 1) {
                $newConstraintParts[$i]--;
            }
        }

        if (!isset($newConstraint)) {
            throw new \Exception('Failed to get the maximum available constraint');
        }
        if ($newConstraintParts === $higherConstraintParts) {
            return $newConstraint;
        }
        return $this->constraintFactory->multiconstraint([
            $this->constraintFactory->constraint('>=', $newConstraint->getPrettyString()),
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
     * Returns an array, each element of which contains part of the version number
     *
     * For example:
     * ```
     * [
     *  0 => some major number
     *  1 => some minor number
     *  2 => some patch number
     * ]
     *
     * @param string $version
     * @return array
     */
    private function getConstraintParts(string $version): array
    {
        preg_match('/(\d*)\.(\d*)\.(\d*)/', $version, $constraintParts);
        return array_slice($constraintParts, 1, 3);
    }
}
