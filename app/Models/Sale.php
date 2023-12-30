<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Service;

class Sale extends Model {
	protected $table = 'islim_sales';

	protected $fillable = [
		'services_id',
        'concentrators_id',
        'assig_pack_id',
        'inv_arti_details_id',
        'api_key',
        'users_email',
        'packs_id',
        'order_altan',
        'unique_transaction',
        'codeAltan',
        'type',
        'id_point',
        'description',
        'fee_paid',
        'amount',
        'amount_net',
        'com_amount',
        'msisdn',
        'conciliation',
        'lat',
        'lng',
        'position',
        'date_reg',
        'status',
        'sale_type'
    ];

    public $timestamps = false;

    /*Retorna monto total que ha pagado un cliente en recargas*/
    public static function getTotalPayment($dn = false){
        if($dn){
            return Sale::where([
                            ['msisdn', $dn],
                            ['status', '!=', 'T'],
                            ['type', 'R']
                          ])
                          ->sum('fee_paid');
        }

        return 0;
    }

    /*Retorna el alta de un dn dado*/
    public static function getRegisterDn($dn = false){
        if($dn){
            return Sale::where([
                            ['type', 'P'],
                            ['status', '!=', 'T'],
                            ['msisdn', $dn]
                        ])
                        ->first();
        }

        return null;
    }

    public static function getLastService($msisdn = false){
        if($msisdn){
            $sale = self::select('services_id')
                          ->where('msisdn', $msisdn)
                          ->orderBy('id', 'DESC')
                          ->first();

            if(!empty($sale)){
                return Service::getServiceById($sale->services_id);
            }
        }

        return null;
    }

    public static function isOldOffert($msisdn = false){
        return false;
        if($msisdn){
            $raw = DB::raw('(SELECT codeAltan 
                        FROM islim_sales AS s 
                        WHERE s.msisdn = islim_sales.msisdn 
                        AND s.codeAltan LIKE "11%" 
                        AND s.status IN ("A","E") 
                        ORDER BY s.id DESC LIMIT 1) as lastCode');

            $data = self::select(
                            'islim_sales.msisdn',
                            'islim_sales.codeAltan as firstCode',
                            $raw
                        )
                        ->where([
                            ['islim_sales.type', 'P'],
                            ['islim_sales.msisdn', $msisdn]
                        ])
                        ->whereIn('islim_sales.status', ['A', 'E'])
                        ->first();

            if(!empty($data)){
                $oldOfferts = [
                  '1100500044',
                  '1101000042',
                  //'1100500043',
                  '1100501000',
                  '1100500040',
                  '1100501001',
                  '1100500042',
                  //'1100501003',
                  '1100501002',
                  '1100500041'
                ];

                if(in_array($data->lastCode, $oldOfferts)){
                    return true;
                }
            }
        }

        return false;
    }

    public static function getSaleByTransaction($transaction = false){
        if($transaction){
            return self::select('msisdn')
                        ->where('unique_transaction', $transaction)
                        ->first();
        }

        return null;
    }
}