<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class UserActivityLog extends Model
{
    public const RETENTION_DAYS = 7;

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'module',
        'menu_label',
        'path',
        'description',
        'metadata',
        'ip_address',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hapus otomatis log yang usianya lebih dari RETENTION_DAYS (7 hari).
     * Berjalan otomatis dengan throttle cache 6 jam agar ringan tanpa bergantung cron cPanel.
     */
    public static function pruneExpiredLogs(bool $force = false): int
    {
        try {
            if (! Schema::hasTable('user_activity_logs')) {
                return 0;
            }

            if (! $force && ! Cache::add('user_activity_logs_prune_throttle', true, now()->addHours(6))) {
                return 0;
            }

            return (int) static::query()
                ->where('created_at', '<', now()->subDays(self::RETENTION_DAYS))
                ->delete();
        } catch (Throwable) {
            return 0;
        }
    }
}
