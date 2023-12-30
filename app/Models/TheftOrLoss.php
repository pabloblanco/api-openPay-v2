<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TheftOrLoss extends Model
{
    protected $table = 'islim_theft_Loss_msisdn';

    protected $fillable = [
        'msisdn',
        'orderId',
        'date_reg',
        'date_active',
        'status'
    ];

    public $timestamps = false;
}
