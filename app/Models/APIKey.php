<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class APIKey extends Model {
    protected $table = 'islim_api_keys';

    protected $fillable = [
        'api_key',
        'concentrators_id',
        'type',
        'date_reg',
        'status'
    ];

    protected $primaryKey = 'api_key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    /*Retorna info del concentrador dado un key*/
    public static function getInfConc($key = false){
        if($key){
            return APIKey::select('islim_concentrators.*')
                           ->join('islim_concentrators', 'islim_concentrators.id', 'islim_api_keys.concentrators_id')
                           ->where([
                            ['islim_api_keys.status', 'A'], 
                            ['islim_api_keys.api_key', env('API_KEY_ALTAM')]
                           ])
                           ->first();
        }
        return false;
    }
}