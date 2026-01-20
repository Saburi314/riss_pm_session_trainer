<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityTrivia extends Model
{
    use HasFactory;

    protected $table = 'security_trivia';

    protected $fillable = [
        'content',
        'category',
    ];
}
