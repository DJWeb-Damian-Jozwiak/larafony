<?php

declare(strict_types=1);

namespace Larafony\Framework\Log\Handlers;

use Larafony\Framework\DBAL\Models\Entities\DatabaseLog;
use Larafony\Framework\Log\Contracts\HandlerContract;
use Larafony\Framework\Log\Formatters\JsonFormatter;
use Larafony\Framework\Log\Message;

final class DatabaseHandler implements HandlerContract
{
    private readonly JsonFormatter $formatter;

    public function __construct()
    {
        $this->formatter = new JsonFormatter();
    }

    public function handle(Message $message): void
    {
        new DatabaseLog()->fill($this->formatter->toArray($message))->save();
    }
}
