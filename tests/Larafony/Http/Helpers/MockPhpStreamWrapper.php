<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Helpers;

class MockPhpStreamWrapper
{
    public static $content;
    public mixed $context = null;
    private $position = 0;
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        return true;
    }

    public function stream_read($count)
    {
        $remaining = strlen(self::$content) - $this->position;
        $count = min($count, $remaining);
        $data = substr(self::$content, $this->position, $count);
        $this->position += $count;
        return $data;
    }

    public function stream_write($data)
    {
        self::$content .= $data;
        return strlen($data);
    }

    public function stream_eof()
    {
        return $this->position >= strlen(self::$content);
    }

    public function stream_stat()
    {
        return [];
    }
}