<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
error_reporting(E_ALL);
date_default_timezone_set('UTC');

require_once __DIR__ . '/autoload.php';

$handler = new \Magento\MagentoCloud\App\ErrorHandler();
set_error_handler([$handler, 'handle']);

return new \Magento\MagentoCloud\App\Container(ECE_BP, BP);
