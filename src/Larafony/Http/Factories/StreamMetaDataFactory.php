<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Factories;

use Larafony\Framework\Http\Helpers\Stream\StreamMetaData;
use Larafony\Framework\Http\Helpers\Stream\StreamWrapper;

class StreamMetaDataFactory
{
    public static function fromStream(StreamWrapper $stream): StreamMetaData
    {
        return new StreamMetaData(...stream_get_meta_data($stream->stream));
    }
}
