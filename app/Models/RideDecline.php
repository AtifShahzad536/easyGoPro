<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RideDecline extends Model
{
    protected $fillable = ['ride_id', 'driver_id','status'];
}
