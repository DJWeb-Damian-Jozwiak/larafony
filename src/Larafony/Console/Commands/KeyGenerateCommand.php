<?php

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Encryption\KeyGenerator;
use Larafony\Framework\Storage\EnvFileHandler;

#[AsCommand('key:generate')]
class KeyGenerateCommand extends Command
{
    private const string KEY_LINE = 'APP_KEY';

    public function run(): int
    {
        new EnvFileHandler()->update(self::KEY_LINE, new KeyGenerator()->generateKey());
        $this->output->success('APP_KEY regenerated successfully.');
        return 0;
    }
}