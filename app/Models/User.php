<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'username',
        'fullname',
        'identity',
        'semester',
        'email',
        'password',
        'student_unit_id', // Untuk relasi student ke unit
        'dosbing_user_id', // Untuk relasi student ke user sebagai dosen pembimbing
        'doswal_user_id', // Untuk relasi student ke user sebagai dosen wali
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $roles_belong_to_dosen = ['dekan', 'kaprodi', 'dosen_pembimbing']; // belum dipakai

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function studentUnit()
    {
        return $this->belongsTo(Unit::class, 'student_unit_id', 'id'); // MENDAPATKAN PRODI MAHASISWA
    }

    /**
     * Filter user yang prodi-nya (student_unit_id) sama dengan unit tertentu.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForStudentUnit($query, int $unitId)
    {
        return $query->where('student_unit_id', $unitId);
    }

    public function dosbingUser()
    {
        return $this->belongsTo(self::class, 'dosbing_user_id', 'id');
    }

    public function doswalUser()
    {
        return $this->belongsTo(self::class, 'doswal_user_id', 'id');
    }

    public function dosbingStudents()
    {
        return $this->hasMany(self::class, 'dosbing_user_id', 'id');
    }

    public function doswalStudents()
    {
        return $this->hasMany(self::class, 'doswal_user_id', 'id');
    }

    /**
     * Relasi HasMany ke Unit untuk user yang berperan sebagai kaprodi melalui user_id.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kaprodiUnits()
    {
        return $this->hasMany(Unit::class, 'kaprodi_user_id', 'id'); // Relasi melalui kolom user_id di tabel units
    }

    /**
     * Baris pivot di unit_users (semua role_as).
     */
    public function unitUsers()
    {
        return $this->hasMany(UnitUser::class);
    }

    // Definisikan relasi many-to-many dengan Unit untuk dosen
    public function dosenUnits()
    {
        return $this->belongsToMany(Unit::class, 'unit_users', 'user_id', 'unit_id')
            ->using(UnitUser::class)
            ->wherePivot('role_as', UnitUser::ROLE_DOSEN)
            ->withTimestamps();
    }

    public function adminUnits()
    {
        return $this->belongsToMany(Unit::class, 'unit_users', 'user_id', 'unit_id')
            ->using(UnitUser::class)
            ->wherePivot('role_as', UnitUser::ROLE_ADMIN_PRODI)
            ->withTimestamps();
    }

    /**
     * ID unit dari pivot unit_users untuk admin_prodi. Null bila user bukan admin_prodi.
     *
     * @return Collection<int, int>|null
     */
    public function adminProdiUnitIds(): ?Collection
    {
        if (! $this->hasRole('admin_prodi')) {
            return null;
        }

        $this->loadMissing('adminUnits');

        return $this->adminUnits->pluck('id');
    }

    public function unitStaseUsers() // Ini hanya untuk role dosen
    {
        return $this->hasMany(UnitStaseUser::class); // Relasi melalui kolom user_id di tabel units
    }

    public function UnitStases()
    {
        return $this->belongsToMany(UnitStase::class, 'unit_stase_users', 'user_id', 'unit_stase_id');
    }

    public function consults()
    {
        return $this->hasMany(Consult::class);
    }

    public function speaks()
    {
        return $this->hasMany(Speak::class);
    }

    /**
     * Scope untuk mengambil pengguna dengan role 'student'.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStudent($query)
    {
        return $query->whereHas('roles', function ($query) {
            $query->where('name', 'student');
        });
    }

    public function scopeDosen($query)
    {
        return $query->whereHas('roles', function ($query) {
            $query->where('name', 'dosen');
        });
    }

    public function scopeKaprodi($query)
    {
        return $query->whereHas('roles', function ($query) {
            $query->where('name', 'kaprodi');
        });
    }

    protected static function booted()
    {
        static::addGlobalScope('latest', function ($query) {
            $query->orderBy('users.created_at', 'desc');
        });
    }

    public function receivesBroadcastNotificationsOn()
    {
        return 'user.notification.'.$this->id;
    }
}
