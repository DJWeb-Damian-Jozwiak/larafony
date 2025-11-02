<?php

namespace Larafony\Framework\Tests\Session;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Encryption\KeyGenerator;
use Larafony\Framework\Storage\Session\Handlers\FileSessionHandler;
use Larafony\Framework\Storage\Session\SessionSecurity;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Tests\TestCase;

class FileSessionStorageTest extends TestCase
{
    private Application $app;
    private string $tempDir;
    private FileSessionHandler $storage;
    private SessionSecurity $security;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = Application::instance();

        $config = $this->createMock(ConfigContract::class);
        $config->method('get')->willReturn(new KeyGenerator()->generateKey());
        $this->app->set(ConfigContract::class, $config);

        $this->tempDir = sys_get_temp_dir() . '/sessions_' . uniqid();
        mkdir($this->tempDir);

        $this->security = new SessionSecurity();
        $this->storage = new FileSessionHandler($this->tempDir, $this->security);
    }
    public function tearDown(): void
    {
        parent::tearDown();
        array_map('unlink', glob($this->tempDir . '/*'));
        rmdir($this->tempDir);
    }

    public function testOpenClose()
    {
        $this->assertTrue( $this->storage->open( 'test', 'test' ) );
        $this->assertTrue( $this->storage->close() );
    }



    public function testCanWriteAndReadSession(): void
    {
        $id = 'test_session';
        $data = ['user_id' => 1, 'name' => 'Test'];

        $this->storage->write($id, json_encode($data));
        $result = $this->storage->read($id);

        $this->assertEquals(json_encode($data), $result);
    }

    public function testReturnsEmptyArrayForNonexistentSession(): void
    {
        $result = $this->storage->read('nonexistent');
        $this->assertEquals('', $result);
    }

    public function testCanDestroySession(): void
    {
        $id = 'test_session';
        $data = ['test' => 'data'];

        $this->storage->write($id, json_encode($data));
        $this->storage->destroy($id);

        $result = $this->storage->read($id);
        $this->assertEquals('', $result);
    }

    public function testGarbageCollectionRemovesOldSessions(): void
    {
        $id1 = 'session1';
        $id2 = 'session2';

        $this->storage->write($id1, json_encode(['test' => 'data1']));
        $this->storage->write($id2, json_encode(['test' => 'data2']));

        touch($this->tempDir . '/sess_' . $id1, time() - 3600);

        $this->storage->gc(1800);

        $this->assertEquals('', $this->storage->read($id1));
        $this->assertNotEquals('', $this->storage->read($id2));
    }

}
