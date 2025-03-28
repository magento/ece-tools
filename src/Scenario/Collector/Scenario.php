<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario\Collector;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;
use Magento\MagentoCloud\Scenario\PathResolver;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Collects scenario data
 */
class Scenario
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var XmlEncoder
     */
    private $encoder;

    /**
     * @var PathResolver
     */
    private $pathResolver;

    /**
     * @param PathResolver $pathResolver
     * @param File $file
     * @param XmlEncoder $encoder
     */
    public function __construct(PathResolver $pathResolver, File $file, XmlEncoder $encoder)
    {
        $this->pathResolver = $pathResolver;
        $this->file = $file;
        $this->encoder = $encoder;
    }

    /**
     * Collect scenario data
     *
     * @param string $scenario
     * @return array
     * @throws ValidationException
     */
    public function collect(string $scenario): array
    {
        try {
            $scenarioPath = $this->pathResolver->resolve($scenario);

            return $this->encoder->decode(
                $this->file->fileGetContents($scenarioPath),
                XmlEncoder::FORMAT
            ) ?: [];
        } catch (FileSystemException $exception) {
            throw new ValidationException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
