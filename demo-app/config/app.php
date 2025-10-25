<?php

declare(strict_types=1);

use Larafony\Framework\Config\Environment\EnvReader;

return [
    'name' => EnvReader::read('APP_NAME'),
    'url' => EnvReader::read('APP_URL'),
];
