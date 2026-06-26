<?php

namespace App\Models\Usuarios;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'link',
    ];

    public function submenus(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }
}
