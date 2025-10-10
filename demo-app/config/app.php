<?php

use Larafony\Framework\Config\Environment\EnvReader;

return [
    'name' => EnvReader::read('APP_NAME'),
    'url' => EnvReader::read('APP_URL'),
];