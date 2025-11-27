<?php

use Larafony\Framework\Config\Environment\EnvReader;

return [
    'name' => 'Larafony',
    'debug' => EnvReader::read('APP_DEBUG'),
];