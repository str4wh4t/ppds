<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitStase extends Model
{
    use HasFactory;

    /**
     * Specify the fillable fields for mass assignment.
     *
     * @var array
     */
    protected $fillable = [
        'is_mandatory',
    ];

    public function stase()
    {
        return $this->belongsTo(Stase::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function unitStaseUsers()
    {
        return $this->hasMany(UnitStaseUser::class); // Relasi melalui kolom user_id di tabel units
    }

    public function users() // Hanya untuk role dosen
    {
        return $this->belongsToMany(User::class, 'unit_stase_users');
    }
}
