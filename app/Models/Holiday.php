<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['date', 'name'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * Tập hợp ngày lễ (dạng 'Y-m-d') trong khoảng, cache theo request để tránh truy vấn lặp.
     *
     * @return array<string, string> map ngày => tên ngày lễ
     */
    public static function map(?string $from = null, ?string $to = null): array
    {
        $query = static::query();
        if ($from) {
            $query->where('date', '>=', $from);
        }
        if ($to) {
            $query->where('date', '<=', $to);
        }

        return $query->get()
            ->mapWithKeys(fn (Holiday $h) => [$h->date->toDateString() => $h->name])
            ->all();
    }
}
