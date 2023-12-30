<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackPrices extends Model
{
    protected $table = 'islim_pack_prices';

	protected $fillable = [
        'id',
        'pack_id',
        'service_id',
        'type',
        'price_pack',
        'price_serv',
        'total_price',
        'status',
        'id_financing'
    ];

    public $timestamps = false;
}