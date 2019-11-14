<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\WarmUp\UrlsPattern;

use Magento\MagentoCloud\App\GenericException;

/**
 * Trows in case when result from config:show:urls command can't be decoded as json.
 */
class ParseResultException extends GenericException
{
}
