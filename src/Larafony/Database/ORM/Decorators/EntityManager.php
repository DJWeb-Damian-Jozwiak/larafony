<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Decorators;

use Larafony\Framework\Core\Support\Str;
use Larafony\Framework\Database\ORM\Model;

class EntityManager
{
    private readonly EntityUpdater $updater;
    private readonly EntityInserter $inserter;

    public function __construct(private Model $model)
    {
        $this->updater = new EntityUpdater($this->model);
        $this->inserter = new EntityInserter($this->model);
    }

    public function save(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $id = $reflection->getProperty('id');
        if (! $id->isInitialized($this->model) && $this->model->use_uuid) {
            $this->model->id = Str::uuid();
            $this->inserter->insert();
        } elseif ($this->model->observer->is_new) {
            $this->model->id = $this->inserter->insert();
        } else {
            $this->updater->update();
        }
    }
}
