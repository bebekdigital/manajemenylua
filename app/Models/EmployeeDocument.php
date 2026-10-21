<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'category',
        'record_type',
        'record_id',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
    ];

    protected $appends = [
        'formatted_file_size',
        'preview_url',
        'download_url',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function getPreviewUrlAttribute(): string
    {
        return route('staff.documents.preview', [
            'id' => $this->employee_id,
            'documentId' => $this->id,
        ]);
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('staff.documents.download', [
            'id' => $this->employee_id,
            'documentId' => $this->id,
        ]);
    }
}
