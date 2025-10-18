<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Relations;

use Larafony\Framework\Database\Base\Query\Enums\JoinType;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;

class BelongsToMany extends HasMany
{
    /**
     * @param Model $parent
     * @param class-string<Model> $related
     * @param string $pivot_table
     * @param string $foreign_pivot_key
     * @param string $related_pivot_key
     */
    public function __construct(
        protected Model $parent,
        protected string $related,
        protected string $pivot_table,
        protected string $foreign_pivot_key,
        protected string $related_pivot_key
    ) {
        parent::__construct($parent, $related, $foreign_pivot_key, 'id');
    }

    public function addConstraints(): void
    {
        $this->query
            ->join(
                table: $this->pivot_table,
                first: $this->pivot_table . '.' . $this->related_pivot_key,
                operator: '=',
                second: $this->related::getTable() . '.id',
                type: JoinType::INNER
            )
            ->where(
                $this->pivot_table . '.' . $this->foreign_pivot_key,
                '=',
                $this->parent->id
            );
    }

    /**
     * Attach related models to the pivot table.
     *
     * @param array<int|string> $ids IDs of related models to attach
     *
     * @return void
     */
    public function attach(array $ids): void
    {
        foreach ($ids as $id) {
            DB::table($this->pivot_table)->insert([
                $this->foreign_pivot_key => $this->parent->id,
                $this->related_pivot_key => $id,
            ]);
        }
    }

    /**
     * Detach related models from the pivot table.
     *
     * @param array<int|string>|null $ids IDs to detach (null = detach all)
     *
     * @return void
     */
    public function detach(?array $ids = null): void
    {
        $query = DB::table($this->pivot_table)
            ->where($this->foreign_pivot_key, '=', $this->parent->id);

        if ($ids !== null) {
            $query->whereIn($this->related_pivot_key, $ids);
        }

        $query->delete();
    }

    /**
     * Sync pivot table (detach all, then attach new).
     *
     * @param array<int|string> $ids IDs to sync
     *
     * @return void
     */
    public function sync(array $ids): void
    {
        $this->detach(); // Remove all existing
        $this->attach($ids); // Attach new ones
    }
}
