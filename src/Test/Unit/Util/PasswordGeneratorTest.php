<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Util\PasswordGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class PasswordGeneratorTest extends TestCase
{
    /**
     * @var PasswordGenerator
     */
    private $generator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->generator = new PasswordGenerator();
    }

    public function testGenerateSaltAndHash()
    {
        $this->generator->generateSaltAndHash('some password');
    }

    public function testGenerateRandomPassword()
    {
        $this->generator->generateRandomPassword();
    }

    public function testGenerateRandomString()
    {
        $this->generator->generateRandomString(5);
    }
}
