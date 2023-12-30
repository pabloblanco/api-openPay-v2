<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PackPrices;

class Financing extends Model
{
    protected $table = 'islim_financing';

    protected $fillable = [
        'name',
        'amount_financing',
        'total_amount',
        'SEMANAL',
        'MENSUAL',
        'QUINCENAL',
        'date_reg',
        'status'
    ];

    public $timestamps = false;

    /*Retorna los datos del financiamiento dado el id de un pack y el id de un servicio*/
    public static function getFinancing($pack = false, $service = false){
    	if($pack && $service){
    		return PackPrices::select('islim_financing.*')
    						   ->join('islim_financing', 'islim_financing.id', 'islim_pack_prices.id_financing')
    						   ->where([
    						   	['islim_pack_prices.pack_id', $pack],
    						   	['islim_pack_prices.service_id', $service],
    						   	['islim_pack_prices.type', 'CR']
    						   ])
    						   ->first();
    	}

    	return null;
    }
}