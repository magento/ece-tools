<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Service\Php;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Flushes the contents of the opcache if the PHP CLI opcache is enabled
 */
class PhpOpcacheReset implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Php
     */
    private $php;

    /**
     * @param LoggerInterface $logger
     * @param Php $php
     */
    public function __construct(
        LoggerInterface $logger,
        Php $php
    ) {
        $this->logger = $logger;
        $this->php = $php;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        if ($this->php->isOpcacheCliEnabled()) {
            $this->logger->notice('Reset the contents of the opcache');
            $this->php->resetOpcache();
        }
    }
}
