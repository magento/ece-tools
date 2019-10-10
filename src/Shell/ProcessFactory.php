<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

/**
 * Creates instance of ProcessInterface
 */
class ProcessFactory
{
    /**
     * Creates instance of Process
     *
     * @param array $params
     * @return Process|ProcessInterface
     * @throws \RuntimeException if Process can't be created
     */
    public function create(array $params): ProcessInterface
    {
        return new Process(
            $params['command'],
            $params['cwd'],
            null,
            null,
            $params['timeout']
        );
    }
}
