<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stase extends Model
{
    use HasFactory;

    /**
     * Specify the fillable fields for mass assignment.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
    ];

    // Relasi Stase ke Unit (many-to-many)
    public function units()
    {
        return $this->belongsToMany(Unit::class, 'unit_stases');
    }

    public function unitStases()
    {
        return $this->hasMany(UnitStase::class);
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'stase_locations');
    }

    protected static function booted()
    {
        static::addGlobalScope('latest', function ($query) {
            $query->orderBy('stases.created_at', 'desc');
        });
    }
}
