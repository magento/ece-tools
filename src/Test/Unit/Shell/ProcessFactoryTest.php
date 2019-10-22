<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Shell;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Shell\Process;
use Magento\MagentoCloud\Shell\ProcessFactory;
use Magento\MagentoCloud\Shell\ProcessInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ProcessFactoryTest extends TestCase
{
    public function testCreate()
    {
        $processFactory = new ProcessFactory();
        /** @var ProcessInterface|MockObject $processMock */
        $params = [
            'command' => 'ls -la',
            'cwd' => '/home/',
            'timeout' => 0,
        ];

        $process = $processFactory->create($params);

        $this->assertInstanceOf(Process::class, $process);
    }
}
