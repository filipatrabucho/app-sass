<?php

namespace App\Core\Employees\Models;

use App\Core\Audit\Concerns\Auditable;
use App\Core\Auth\Models\User;
use App\Core\Departments\Models\Department;
use App\Core\Departments\Models\Position;
use App\Core\Files\Models\Attachment;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $first_name
 * @property string $last_name
 * @property int|null $manager_id
 * @property int|null $department_id
 * @property string $status
 * @property Carbon|null $hire_date
 * @property-read string $full_name
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use Auditable, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'user_id', 'employee_number', 'external_id', 'first_name', 'last_name', 'email', 'nif', 'birth_date',
        'phone', 'address', 'iban', 'department_id', 'position_id', 'manager_id', 'hire_date', 'avatar_path', 'status',
    ];

    protected $hidden = ['iban'];

    /** @var list<string> */
    protected array $auditMasked = ['iban'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
            'address' => 'array',
            'iban' => 'encrypted',
        ];
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * IDs de todos os subordinados (diretos e indiretos).
     *
     * @return list<int>
     */
    public function subordinateIds(): array
    {
        $ids = [];
        $frontier = [$this->id];

        while ($frontier !== []) {
            $frontier = self::query()->whereIn('manager_id', $frontier)->pluck('id')->diff($ids)->values()->all();
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    /**
     * $other é chefia (direta ou indireta) deste colaborador?
     */
    public function isManagedBy(self $other): bool
    {
        return in_array($this->id, $other->subordinateIds(), true);
    }
}
