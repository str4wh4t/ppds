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

    public function unitDosens()
    {
        return $this->belongsToMany(User::class, 'unit_users', 'unit_id', 'user_id')
            ->wherePivot('role_as', 'dosen'); // Filter berdasarkan role_as = 'dosen'
    }

    public function unitAdmins()
    {
        return $this->belongsToMany(User::class, 'unit_users', 'unit_id', 'user_id')
            ->wherePivot('role_as', 'admin_prodi'); // Filter berdasarkan role_as = 'admin_prodi'
    }

    protected static function booted()
    {
        static::addGlobalScope('latest', function ($query) {
            $query->orderBy('units.created_at', 'desc');
        });
    }
}
