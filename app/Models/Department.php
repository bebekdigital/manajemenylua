<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Department extends Model
{
    protected $fillable = ['name', 'unit_group', 'order'];

    /**
     * Invalidate organization caches when departments or positions change.
     */
    public static function forgetOrganizationCaches(): void
    {
        Cache::forget('departments_with_positions');
        Cache::forget('school_units_list');
        Cache::forget('available_units_list');
        Cache::forget('staff_switcher_list_admin');

        if (Schema::hasTable('users')) {
            User::query()->pluck('id')->each(function (int $userId): void {
                Cache::forget('staff_switcher_list_'.$userId);
            });
        }
    }

    /**
     * Register model event hooks to keep cached organization lists in sync.
     */
    protected static function booted(): void
    {
        static::saved(function (): void {
            static::forgetOrganizationCaches();
        });

        static::deleted(function (): void {
            static::forgetOrganizationCaches();
        });
    }

    /**
     * @return HasMany<DepartmentPosition, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(DepartmentPosition::class)->orderBy('order')->orderBy('name');
    }
}
