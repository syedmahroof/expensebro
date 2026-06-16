<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'affiliation_number',
        'stream',
        'college_name',
        'address',
        'phone_primary',
        'phone_secondary',
    ];
}
