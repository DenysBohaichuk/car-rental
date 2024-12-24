<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RcCarsBrandsTranslation extends Model
{
    use HasFactory;


    public function rcCarBrand()
    {
        return $this->belongsTo(RcCarsBrand::class, 'car_brand_id');
    }
}
