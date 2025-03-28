<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;

/**
 * Resolves path to the scenario.
 * Throws an exception if scenario file can't be found.
 */
class PathResolver
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @param File $file
     * @param SystemList $systemList
     */
    public function __construct(File $file, SystemList $systemList)
    {
        $this->file = $file;
        $this->systemList = $systemList;
    }

    /**
     * Resolves path to the scenario.
     *
     * @param string $scenarioPath
     * @return string
     * @throws ValidationException if scenario file can't be found
     */
    public function resolve(string $scenarioPath): string
    {
        $files = [
            $scenarioPath,
            $this->systemList->getRoot() . '/' . $scenarioPath,
            $this->systemList->getMagentoRoot() . '/' . $scenarioPath
        ];

        foreach ($files as $filePath) {
            if ($this->file->isExists($filePath)) {
                return $filePath;
            }
        }

        throw new ValidationException(sprintf('Scenario %s does not exist', $scenarioPath));
    }
}
