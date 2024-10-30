<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitStaseUser extends Model // ini hanya untuk relasi dosen dengan stase yang ada pada unit
{
    use HasFactory;

    public function unitStase()
    {
        return $this->belongsTo(UnitStase::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
