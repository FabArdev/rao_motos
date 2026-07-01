<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    protected $table = 'page_visits';

    protected $fillable = ['ruta', 'contador'];

    protected $casts = ['contador' => 'integer'];
}
