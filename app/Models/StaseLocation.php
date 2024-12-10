<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaseLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'stase_id',
        'location_id',
    ];

    public function stase()
    {
        return $this->belongsTo(Stase::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
