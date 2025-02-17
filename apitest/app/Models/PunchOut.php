<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PunchOut extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'timestamp',
    ];
}
