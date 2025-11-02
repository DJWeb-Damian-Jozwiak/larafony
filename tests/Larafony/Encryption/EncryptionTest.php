<?php

namespace Larafony\Framework\Tests\Encryption;

use Larafony\Framework\Web\Application;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Encryption\EncryptionService;
use Larafony\Framework\Encryption\KeyGenerator;
use Larafony\Framework\Storage\EnvFileHandler;
use Larafony\Framework\Storage\File;
use InvalidArgumentException;
use Larafony\Framework\Tests\TestCase;

class EncryptionTest extends TestCase
{
    public function testEncryptDecryptWithoutKey()
    {
        $app = Application::instance(dirname(__DIR__));
        $config = $this->createMock(ConfigContract::class);
        $config->expects($this->once())->method('get')
            ->willReturn(null);
        $app->set(ConfigContract::class, $config);
        $data = 'test';
        $this->expectException(\RuntimeException::class);
        new EncryptionService()->encrypt($data);
    }

    public function testEncryptDecryptWithWrongKey()
    {
        $app = Application::instance(dirname(__DIR__));
        $data = 'test';
        $config = $this->createMock(ConfigContract::class);
        $config->expects($this->once())->method('get')
            ->willReturn('invalid');
        $app->set(ConfigContract::class, $config);
        $this->expectException(\InvalidArgumentException::class);
        new EncryptionService()->encrypt($data);
    }

    public function testEncryptDecrypt()
    {
        $app = Application::instance(dirname(__DIR__));
        $data = 'test';
        $config = $this->createMock(ConfigContract::class);
        $config->expects($this->any())->method('get')
            ->willReturn(new KeyGenerator()->generateKey());
        $app->set(ConfigContract::class, $config);
        $encrypted = new EncryptionService()->encrypt($data);
        $encrypted2 = new EncryptionService()->encrypt($data);
        $this->assertNotEquals($data, $encrypted);
        $this->assertNotEquals($encrypted, $encrypted2);
        $decrypted = new EncryptionService()->decrypt($encrypted);
        $this->assertEquals($data, $decrypted);
    }

    public function testThrowsExceptionOnInvalidBase64(): void
    {
        $app = Application::instance(dirname(__DIR__));
        $config = $this->createMock(ConfigContract::class);
        $config->expects($this->any())->method('get')
            ->willReturn(new KeyGenerator()->generateKey());
        $app->set(ConfigContract::class, $config);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64 encoding');
        new EncryptionService()->decrypt('!@#$%^&*');
    }

    public function testThrowsExceptionOnDataTooShort(): void
    {
        $app = Application::instance(dirname(__DIR__));
        $config = $this->createMock(ConfigContract::class);
        $config->expects($this->any())->method('get')
            ->willReturn(new KeyGenerator()->generateKey());
        $app->set(ConfigContract::class, $config);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Data is too short');

        new EncryptionService()->decrypt(base64_encode('short'));
    }

    public function testThrowsExceptionOnDecryptionFailure(): void
    {
        $app = Application::instance(dirname(__DIR__));
        $config = $this->createMock(ConfigContract::class);
        $config->expects($this->any())->method('get')
            ->willReturn(new KeyGenerator()->generateKey());
        $app->set(ConfigContract::class, $config);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decryption failed');

        $fakeNonce = random_bytes(24); // NONCE_LENGTH
        $fakeCiphertext = random_bytes(32);
        $fakeEncrypted = base64_encode($fakeNonce . $fakeCiphertext);

        new EncryptionService()->decrypt($fakeEncrypted);
    }
}
