<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'credits',
        'features',
        'color',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features'  => 'array',
        'is_active' => 'boolean',
        'price'     => 'float',
        'credits'   => 'integer',
        'sort_order'=> 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'plan_id');
    }

    /**
     * Scope to only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get badge color class for Tailwind.
     */
    public function getBadgeClassAttribute(): string
    {
        return match ($this->color) {
            'indigo'  => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
            'purple'  => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
            'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
            'rose'    => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
            default   => 'bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-300',
        };
    }
}
