<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePermit extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'tanggal',
        'tanggal_selesai',
        'jenis',
        'detail',
        'keterangan',
        'lampiran_path',
        'lampiran_nama',
        'status',
        'catatan',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'formatted_tanggal',
        'preview_url',
        'download_url',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getFormattedTanggalAttribute(): ?string
    {
        if (! $this->tanggal) {
            return null;
        }

        if ($this->tanggal_selesai && $this->tanggal_selesai->ne($this->tanggal)) {
            return Carbon::parse($this->tanggal)->translatedFormat('d M Y').' - '.Carbon::parse($this->tanggal_selesai)->translatedFormat('d M Y');
        }

        return Carbon::parse($this->tanggal)->translatedFormat('d M Y');
    }

    public function getPreviewUrlAttribute(): ?string
    {
        if (! $this->lampiran_path) {
            return null;
        }

        return route('staff.permits.preview', [
            'id' => $this->employee_id,
            'permitId' => $this->id,
        ]);
    }

    public function getDownloadUrlAttribute(): ?string
    {
        if (! $this->lampiran_path) {
            return null;
        }

        return route('staff.permits.download', [
            'id' => $this->employee_id,
            'permitId' => $this->id,
        ]);
    }
}
