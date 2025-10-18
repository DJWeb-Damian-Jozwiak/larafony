<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Database\Schema;

#[AsCommand(name: 'create:db-logs')]
class CreateDbLogs extends Command
{
    public function __construct(
        ContainerContract $container,
    ) {
        $output = $container->get(OutputContract::class);
        parent::__construct($output, $container);
    }

    public function run(): int
    {
        try {
            $this->output->info('Creating database_logs table...');

            $sql = Schema::create('database_logs', static function ($table): void {
                $table->integer('id')->autoIncrement(true)->nullable(false);
                $table->string('level', 50)->nullable(false);
                $table->text('message')->nullable(false);
                $table->text('context')->nullable(false);
                $table->text('metadata')->nullable(true);
                $table->timestamp('created_at')->current();
                $table->timestamp('updated_at')->current()->currentOnUpdate();
                $table->primary('id');
            });

            Schema::execute($sql);

            $this->output->success('Table database_logs created successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->output->error('Failed to create database_logs table: ' . $e->getMessage());
            return 1;
        }
    }
}
