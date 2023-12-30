<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sale;
use App\Models\Financing;

class ClientNetwey extends Model {

    protected $table = 'islim_client_netweys';

    protected $fillable = [
        'msisdn',
        'clients_dni',
        'service_id',
        'address',
        'type_buy',
        'periodicity',
        'num_dues',
        'paid_fees',
        'unique_transaction',
        'serviceability',
        'lat',
        'lng',
        'point',
        'date_buy',
        'price_remaining',
        'total_debt',
        'date_reg',
        'date_expire',
        'status',
        'obs',
        'credit',
        'n_update_coord',
        'n_sim_swap',
        'tag',
        'id_list_dns',
        'dn_type'
    ];

    protected $primaryKey = 'msisdn';

    public $incrementing = false;

    public $timestamps = false;

    /*Devuelve los datos de un DN*/
    public static function getDnData($dn = false, $fields = ['*']){
        if($dn){
            return ClientNetwey::select($fields)
            ->where('msisdn', $dn)
            ->whereIn('status', ['A', 'S'])
            ->first();
        }

        return null;
    }

    //Retorna la zona a la que pertenece un DN
    public static function getFiberZoneByDn($dn = false)
    {
        $clientNetwey = self::select('id_fiber_zone')
                        ->where('msisdn', $dn)
                        ->first();

        if (!empty($clientNetwey)) {
          return $clientNetwey->id_fiber_zone;
        }

        return false;
    }

    /*Actualiza los datos del credito que adquirio el cliente*/
    public static function updateCredit($client = false, $service = false, $sale = false){
        if($client && $service && $sale){
            //Buscando alta del dn
            $reg = Sale::getRegisterDn($client->msisdn);

            if($reg){
                //Consultando elfinanciamiento del DN
                $financing = Financing::getFinancing($reg->packs_id, $reg->services_id);

                if(!empty($financing)){
                    //Monto pagado del credito
                    $fee = $financing[$service->periodicity];
                    //Monto restante del credito
                    $remaining = $client->price_remaining - $fee;
                    
                    //Cambiando estatus del credito, en caso de ser ultima cuota
                    if($remaining <= 0){
                        $client->credit = 'P';
                        $remaining = 0;
                    }

                    //Cantidad de cuotas pagadas
                    $dues = $client['num_dues'] + 1;

                    //Actualizado bd
                    $client->num_dues = $dues;
                    $client->price_remaining = $remaining;
                    $client->save();

                    //Actualizando feed en tabla de ventas
                    Sale::where('id', $sale)->update(['fee_paid' => $fee]);

                    return true;
                }
            }
        }

        return false;
    }
}