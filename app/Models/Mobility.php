<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ClientNetwey;

class Mobility extends Model {
	protected $table = 'islim_mobility';

	protected $fillable = [
        'id',
        'file_id',
        'msisdn',
        'imsi',
        'lat',
        'lng',
        'enb',
        'cell_id',
        'dateAltanaffect',
        'date_affec',
        'status',
        'notas'
    ];
    
    public $timestamps = false;

    public static function canBuyChangeHome(ClientNetwey $client){
        if(!empty($client->n_update_coord) && $client->n_update_coord >= 2){
            $data = self::select('msisdn')
                          ->where([
                            ['msisdn', $client->msisdn],
                            ['status', 'A']
                          ])
                          ->first();

            if(!empty($data)){
                return true;
            }
        }

        return false;
    }
}