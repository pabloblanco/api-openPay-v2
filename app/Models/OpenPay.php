<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Utilities\Common;

class OpenPay extends Model
{
    protected $table = 'islim_open_pay';

	protected $fillable = [
        'id',
        'folio',
        'msisdn',
        'local_date',
        'trx_no',
        'authorization',
        'service_id',
        'unique_transaction',
        'amount',
        'date_reg',
        'date_update',
        'status'
    ];

    public $timestamps = false;

    public static function getAuthCod(){
        $aleatorio = Common::getRandDig(6);

        if(OpenPay::where('authorization', $aleatorio)->count())
            return self::getAuthCod();
        else
            return $aleatorio;
    }

    public static function isProcesed($id = fasle){
        $data = self::select('status')
                      ->where('id', $id)
                      ->first();

        if(!empty($data) && $data->status == 'P'){
            return true;
        }

        return false;
    }
}