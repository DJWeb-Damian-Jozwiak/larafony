<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Database\Base\Seeder;
use Larafony\Framework\Web\Config;

#[AsCommand(name: 'database:seed')]
class DatabaseSeed extends Command
{
    #[CommandArgument(name: 'seeder', value: 'DatabaseSeeder', description: 'The class name of the root seeder')]
    protected string $seeder = 'DatabaseSeeder';

    public function run(): int
    {
        $seederNamespace = Config::get('app.seeder_namespace', 'Database\\Seeders\\');
        $rootNamespace = Config::get('app.root_namespace', 'App\\');
        /** @var class-string<Seeder> $seederClass */
        $seederClass = $rootNamespace . $seederNamespace . $this->seeder;

        if (! class_exists($seederClass)) {
            $this->output->error("Seeder class {$seederClass} does not exist.");
            return 1;
        }

        $seeder = new $seederClass();
        $seeder->run();

        $this->output->info('Database seeding completed successfully.');
        return 0;
    }

    protected function rootNamespace(): string
    {
        /** @phpstan-ignore-next-line */
        return $this->container->getBinding('app.root_namespace');
    }
}