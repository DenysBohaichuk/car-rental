<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RcCarsTranslation extends Model
{
    use HasFactory;


    public function rcCar()
    {
        return $this->belongsTo(RcCar::class, 'car_id');
    }
}
