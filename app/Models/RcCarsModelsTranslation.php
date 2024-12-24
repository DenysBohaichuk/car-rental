<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RcCarsModelsTranslation extends Model
{
    use HasFactory;


    public function rcModel()
    {
        return $this->belongsTo(RcCarsModel::class, 'car_model_id');
    }
}
