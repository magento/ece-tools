<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger\Processor;

use Magento\MagentoCloud\App\Logger\Sanitizer;

/**
 * Logger processor for sanitizing sensitive data.
 */
class SanitizeProcessor
{
    /**
     * @var Sanitizer
     */
    private $sanitizer;

    /**
     * @param Sanitizer $sanitizer
     */
    public function __construct(Sanitizer $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    /**
     * Finds and replace sensitive data in record message.
     *
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $record['message'] = $this->sanitizer->sanitize($record['message']);

        return $record;
    }
}
