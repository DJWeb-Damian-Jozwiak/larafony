<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Entities;

use Larafony\Framework\Database\ORM\Attributes\BelongsToMany;
use Larafony\Framework\Database\ORM\Model;

class Permission extends Model
{
    public string $name {
        get => $this->name;
        set {
            $this->name = $value;
            $this->markPropertyAsChanged('name');
        }
    }

    public ?string $description {
        get => $this->description;
        set {
            $this->description = $value;
            $this->markPropertyAsChanged('description');
        }
    }

    /**
     * @return array<Role>
     */
    #[BelongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id')]
    public array $roles {
        get => $this->relations->getRelation('roles');
    }

}
