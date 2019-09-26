<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger;

use Monolog\Formatter\LineFormatter;

/**
 * The factory for LineFormatter.
 */
class LineFormatterFactory
{
    /**
     * @return LineFormatter
     */
    public function create(): LineFormatter
    {
        return new LineFormatter("[%datetime%] %level_name%: %message% %context% %extra%\n", null, true, true);
    }
}
