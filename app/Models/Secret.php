<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Secret extends Model
{
    use HasFactory;

    protected $fillable = [
        'hash',
        'secretText',
        'expiresAt',
        'remainingViews'
    ];

}
