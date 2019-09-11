<?php

namespace LaravelEnso\Permissions\app\Models;

use LaravelEnso\Menus\app\Models\Menu;
use LaravelEnso\Roles\app\Models\Role;
use Illuminate\Database\Eloquent\Model;
use LaravelEnso\Roles\app\Traits\HasRoles;
use LaravelEnso\Permissions\app\Enums\Types;
use LaravelEnso\Tables\app\Traits\TableCache;
use LaravelEnso\Tutorials\app\Models\Tutorial;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Permission extends Model
{
    use HasRoles, TableCache;

    protected $fillable = ['name', 'description', 'type', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function menu()
    {
        return $this->hasOne(Menu::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function tutorials()
    {
        return $this->hasMany(Tutorial::class);
    }

    public function getIsReadAttribute()
    {
        return $this->type === Types::Read;
    }

    public function scopeImplicit($query)
    {
        return $query->whereIsDefault(true);
    }

    public function delete()
    {
        if ($this->roles()->exists()) {
            throw new ConflictHttpException(__(
                'You cannot delete this permission while being allocated to existing role(s)'
            ));
        }

        parent::delete();
    }
}
