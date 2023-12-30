<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ClientNetwey;
use App\Models\TheftOrLoss;

class Suspend extends Model
{
    protected $table = 'islim_suspends';

    protected $fillable = [
        'msisdn',
        'response',
        'date_reg',
        'status'
    ];

    public $timestamps = false;

    public static function canBuySuspend(ClientNetwey $client){
        $isTheft = TheftOrLoss::select('msisdn')
                                ->where([
                                    ['msisdn', $client->msisdn],
                                    ['status', 'A']
                                ])
                                ->first();

        if(empty($isTheft)){
            $data = self::where([
                            ['msisdn', $client->msisdn], 
                            ['status', 'A']
                         ])
                         ->first();

            if(!empty($data) && !empty($data->response)){
                $response = json_decode($data->response);

                if(!empty($response->orderId) && $client->status == 'S'){
                    return true;
                }
            }
        }

        return false;
    }
}