<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    /**
     * Specify the fillable fields for mass assignment.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'kaprodi_user_id',
        'admin_user_id',
        'guideline_document_path',
    ];

    /**
     * Define a relationship to the User model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kaprodiUser()
    {
        return $this->belongsTo(User::class, 'kaprodi_user_id', 'id');
    }

    public function stases()
    {
        return $this->belongsToMany(Stase::class, 'unit_stases', 'unit_id', 'stase_id');
    }

    public function unitStases()
    {
        return $this->hasMany(UnitStase::class);
    }

    /**
     * User yang terdaftar pada unit ini lewat users.student_unit_id (bukan tabel unit_users).
     */
    public function students()
    {
        return $this->hasMany(User::class, 'student_unit_id', 'id');
    }

    /**
     * User di atas yang masih aktif sebagai mahasiswa (is_active_student = 1).
     */
    public function activeStudents()
    {
        return $this->students()->where('is_active_student', 1);
    }

    /**
     * Baris pivot di unit_users untuk unit ini.
     */
    public function unitUsers()
    {
        return $this->hasMany(UnitUser::class, 'unit_id');
    }

    public function unitDosens()
    {
        return $this->belongsToMany(User::class, 'unit_users', 'unit_id', 'user_id')
            ->using(UnitUser::class)
            ->wherePivot('role_as', UnitUser::ROLE_DOSEN)
            ->withTimestamps();
    }

    public function unitAdmins()
    {
        return $this->belongsToMany(User::class, 'unit_users', 'unit_id', 'user_id')
            ->using(UnitUser::class)
            ->wherePivot('role_as', UnitUser::ROLE_ADMIN_PRODI)
            ->withTimestamps();
    }

    protected static function booted()
    {
        static::addGlobalScope('latest', function ($query) {
            $query->orderBy('units.created_at', 'desc');
        });
    }
}
