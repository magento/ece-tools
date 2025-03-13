<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

use Magento\MagentoCloud\App\GenericException;
use Composer\Composer;
use Composer\Repository\RepositoryInterface;
use Composer\Semver\Comparator;

/**
 * Creates instance of ProcessInterface
 */
class ProcessFactory
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @param Composer $composer
     */
    public function __construct(Composer $composer)
    {
        $this->repository = $composer->getLocker()->getLockedRepository();
    }

    /**
     * Creates instance of Process
     *
     * @param array $params
     * @return Process|ProcessInterface
     * @throws GenericException
     */
    public function create(array $params): ProcessInterface
    {
        if ($this->canUseShellFromLine()) {
            return Process::fromShellCommandline(
                $params['command'],
                $params['cwd'],
                null,
                null,
                $params['timeout']
            );
        } else {
            return new Process(
                $params['command'],
                $params['cwd'],
                null,
                null,
                $params['timeout']
            );
        }
    }

    /**
     * Checks if the symfony/process package has method fromShellCommandline
     *
     * The latest versions of this package does not support a command as line through constructor.
     * So, we check a version of the package and if the version is greater than or equal to 4.2.0
     * we use the method fromShellCommandline to create a process object
     *
     * @return bool
     * @throws GenericException
     */
    private function canUseShellFromLine(): bool
    {
        $package = $this->repository->findPackage('symfony/process', '*');

        if ($package === null) {
            throw new GenericException('Could not find symfony/process package.');
        }

        return Comparator::greaterThanOrEqualTo($package->getVersion(), '4.2.0');
    }
}
