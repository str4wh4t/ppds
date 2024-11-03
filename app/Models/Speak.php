<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Speak extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'speak_title',
        'description',
        'speak_document_path',
        'speak_document_size',
        'employee_user_id',
        'answer_title',
        'answer',
        'answer_document_path',
        'answer_document_size',
        'answer_at',
    ];

    public function user() // untuk student
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_user_id', 'id');
    }

    protected static function booted()
    {
        static::addGlobalScope('latest', function ($query) {
            $query->orderBy('created_at', 'desc');
        });
    }
}
