<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Cache toàn bộ cặp key/value trong vòng đời request để tránh N+1
     * khi đọc nhiều cấu hình (đặc biệt lúc render danh sách chấm công).
     */
    protected static ?array $cache = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        if (static::$cache === null) {
            static::$cache = static::query()->pluck('value', 'key')->toArray();
        }

        return static::$cache[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        static::$cache = null;
    }

    public static function pairs(): array
    {
        return static::query()->pluck('value', 'key')->toArray();
    }
}
