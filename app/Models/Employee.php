<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code', 'name', 'email', 'personal_email', 'phone', 'gender', 'dob',
        'national_id', 'marital_status', 'nationality', 'permanent_address',
        'temporary_address', 'department_id', 'position', 'level', 'contract_type',
        'join_date', 'manager_id', 'status', 'bank_name', 'bank_account',
        'bank_holder', 'base_salary', 'lunch_allowance', 'emergency_contact', 'skills',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'join_date' => 'date',
            'skills' => 'array',
            'base_salary' => 'decimal:2',
            'lunch_allowance' => 'decimal:2',
        ];
    }

    public const STATUS_LABELS = [
        'active' => 'Đang làm việc',
        'on_leave' => 'Nghỉ phép',
        'resigned' => 'Đã nghỉ việc',
    ];

    public const GENDER_LABELS = [
        'male' => 'Nam',
        'female' => 'Nữ',
        'other' => 'Khác',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getGenderLabelAttribute(): ?string
    {
        return $this->gender ? (self::GENDER_LABELS[$this->gender] ?? $this->gender) : null;
    }
}
