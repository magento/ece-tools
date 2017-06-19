#!/usr/bin/env php
<?php

require_once __DIR__ . '/autoload.php';

$dbDump = new Magento\MagentoCloud\Command\DBDump();
$dbDump->execute();