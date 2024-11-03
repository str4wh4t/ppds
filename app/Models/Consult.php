<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consult_title',
        'description',
        'consult_document_path',
        'consult_document_size',
        'dosbing_user_id',
        'reply_title',
        'reply',
        'reply_document_path',
        'reply_document_size',
        'reply_at',
    ];

    public function user() // untuk student
    {
        return $this->belongsTo(User::class);
    }

    public function dosbing()
    {
        return $this->belongsTo(User::class, 'dosbing_user_id', 'id');
    }

    protected static function booted()
    {
        static::addGlobalScope('latest', function ($query) {
            $query->orderBy('created_at', 'desc');
        });
    }
}
