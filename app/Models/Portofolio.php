<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portofolio extends Model
{
    use HasFactory;

    /**
     * Specify the fillable fields for mass assignment.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'portofolio_document_path'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::addGlobalScope('latest', function ($query) {
            $query->orderBy('created_at', 'desc');
        });
    }
}
