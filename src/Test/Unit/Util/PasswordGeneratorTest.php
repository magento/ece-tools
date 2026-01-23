<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

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
    protected function setUp(): void
    {
        $this->generator = new PasswordGenerator();
    }

    public function testGenerateSaltAndHash()
    {
        $result = $this->generator->generateSaltAndHash('some password');
        $this->assertIsString($result);
        $parts = explode(':', $result);
        $this->assertCount(3, $parts, 'Result should contain hash:salt:version');
        $this->assertEquals(64, strlen($parts[0]), 'Hash should be 64 characters (SHA-256)');
        $this->assertEquals(32, strlen($parts[1]), 'Salt should be 32 characters');
        $this->assertEquals('1', $parts[2], 'Version should be 1');
    }

    public function testGenerateRandomPassword()
    {
        $result = $this->generator->generateRandomPassword();
        $this->assertIsString($result);
        $this->assertEquals(20, strlen($result), 'Default password length should be 20');
        $this->assertMatchesRegularExpression('/.*[A-Za-z].*/', $result, 'Password should contain letters');
        $this->assertMatchesRegularExpression('/.*[\d].*/', $result, 'Password should contain digits');
    }

    public function testGenerateRandomString()
    {
        $result = $this->generator->generateRandomString(5);
        $this->assertIsString($result);
        $this->assertEquals(5, strlen($result), 'Generated string should have requested length');
        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9]+$/',
            $result,
            'String should only contain alphanumeric characters'
        );
    }
}
