<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Fixtures;

use Larafony\Framework\Validation\Attributes\ValidWhen;

class DataExtractionRequest
{
    public string $field1 = 'value1';
    public string $field2 = 'value2';

    #[ValidWhen(self::hasAllFields(...))]
    public string $field3 = 'value3';

    private static function hasAllFields(mixed $value, array $data): bool
    {
        return isset($data['field1']) && isset($data['field2']);
    }
}
