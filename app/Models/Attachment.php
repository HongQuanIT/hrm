<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'attachable_type', 'attachable_id', 'disk', 'path',
        'original_name', 'mime_type', 'size', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Kích thước dễ đọc: KB / MB.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Biểu tượng Material Symbols theo loại file.
     */
    public function getIconAttribute(): string
    {
        $mime = (string) $this->mime_type;

        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_contains($mime, 'pdf') => 'picture_as_pdf',
            str_contains($mime, 'word'), str_contains($mime, 'document') => 'description',
            str_contains($mime, 'sheet'), str_contains($mime, 'excel'), str_contains($mime, 'csv') => 'table_chart',
            str_contains($mime, 'zip'), str_contains($mime, 'compressed') => 'folder_zip',
            default => 'attach_file',
        };
    }
}
