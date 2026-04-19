<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UnitUser extends Pivot
{
    public const ROLE_ADMIN_PRODI = 'admin_prodi';

    public const ROLE_DOSEN = 'dosen';

    protected $table = 'unit_users';

    /**
     * Pivot punya primary key sendiri (id), bukan composite.
     */
    public $incrementing = true;

    protected $fillable = [
        'unit_id',
        'user_id',
        'role_as',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function scopeAdminProdi($query)
    {
        return $query->where('role_as', self::ROLE_ADMIN_PRODI);
    }

    public function scopeDosen($query)
    {
        return $query->where('role_as', self::ROLE_DOSEN);
    }
}
