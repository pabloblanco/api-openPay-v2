<?php

namespace App\Models;

use App\Models\APIKey;
use App\Models\Financing;
use App\Models\Sale;
use App\Models\ServiceChanel;
use App\Utilities\Common;
use Illuminate\Database\Eloquent\Model;
use Log;

class Service extends Model
{
  protected $table = 'islim_services';

  protected $fillable = [
    'id',
    'periodicity_id',
    'codeAltan',
    'title',
    'description',
    'price_pay',
    'price_remaining',
    'broadband',
    'supplementary',
    'date_reg',
    'status',
    'type',
    'method_pay',
    'gb',
    'plan_type',
    'service_type',
    'primary_service',
    'type_hbb'];

  public $timestamps = false;

  /*Retorna un servicio que puede ser activado por un dn dado y es buscado por medio del monto*/
  public static function getServiceByAmount($dn = false, $amount = false, $serviceability = false, $typeClient = false, $list = false, $dnType = 'H', $father = false, $canBuyChHome = false)
  {
    if ($dn && $amount && $typeClient) {
      $conc = APIKey::getInfConc(env('API_KEY_ALTAM'));

      if (!empty($conc)) {
        $amount = Common::cleanNumber($amount);

        $services = ServiceChanel::select(
          'islim_services.id',
          'islim_services.description',
          'islim_services.title',
          'islim_services.price_pay',
          'islim_services.broadband',
          'islim_services.supplementary',
          'islim_periodicities.periodicity'
        )
          ->join('islim_services', 'islim_services.id', 'islim_service_channel.id_service')
          ->join('islim_periodicities', 'islim_periodicities.id', 'islim_services.periodicity_id')
          ->where([
            ['islim_service_channel.status', 'A'],
            ['islim_services.status', 'A'],
            ['islim_periodicities.status', 'A'],
            ['islim_services.type', 'P'],
            ['islim_services.service_type', $dnType]]);

        //Condiciones en caso de que el dn sea acredito
        if ($typeClient == 'CR') {
          //Buscando alta del dn
          $reg = Sale::getRegisterDn($dn);

          if ($reg) {
            //Consultando elfinanciamiento del DN
            $financing = Financing::getFinancing($reg->packs_id, $reg->services_id);

            if (!empty($financing)) {
              //Filtrando por precios el servicio
              $services = $services->whereIn(
                'islim_services.price_pay',
                [
                  $amount - $financing->SEMANAL,
                  $amount - $financing->QUINCENAL,
                  $amount - $financing->MENSUAL]
              );
            }
          }
        } else {
          //Filtrando por precios el servicio
          $services = $services->where('islim_services.price_pay', $amount);
        }
        //Si el dn no pertenece a una lista se filtran los servicios por canal o por concentrador
        if (!$list) {
          $services = $services->where(function ($query) use ($conc) {
            $query->where(
              'islim_service_channel.id_channel',
              !empty($conc->id_channel) ? $conc->id_channel : 0
            )
              ->orWhere('islim_service_channel.id_concentrator', $conc->id);
          });
        } else {
          //Si el dn pertenece a una lista se filtran los servicios asociados a esa lista
          $services = $services->where('islim_service_channel.id_list_dns', $list);
        }

        if ($canBuyChHome) {
          $services = $services->whereIn('islim_services.id', explode(',', env('CHANGE_COORDS')));
        } else {
          $services = $services->whereNotIn('islim_services.id', explode(',', env('CHANGE_COORDS')));
        }
        
        //No borrar esto puede servir cuando decidan activar recargas
        /*if($dnType == 'T'){
        $services = $services->where('islim_services.id', '!=', $father)
        ->where(function($query) use ($father){
        $query->where('islim_services.primary_service', $father)
        ->orWhereNull('islim_services.primary_service');
        });
        }*/

        if ($typeClient == 'CR') {
          $services = $services->get();
          if (count($services)) {
            foreach ($services as $s) {
              if ($amount == $s->price_pay + $financing->{$s->periodicity}) {
                $service = $s;
                break;
              }
            }
          }
        } else {
          $service = $services->first();
        }

        if (!empty($service)) {
          if (Common::getLastNumber($service->broadband) <= Common::getLastNumber($serviceability) || ($dnType == 'T' || $dnType == 'M' || $dnType == 'MH' || $dnType == 'F')) {
            return $service;
          }
        }
      }
    }

    return null;
  }

  /*Retorna un servicio que puede ser activado por un dn dado y es buscado por medio del monto*/
  public static function getServiceByAmountAndZone($dn = false, $amount = false, $serviceability = false, $typeClient = false, $list = false, $dnType = 'F', $father = false, $canBuyChHome = false)
  {
    if ($dn && $amount && $typeClient) {
      $conc = APIKey::getInfConc(env('API_KEY_ALTAM'));
      $fiber_zone_id = ClientNetwey::getFiberZoneByDn($dn);

      if (!empty($conc)) {
        $amount = Common::cleanNumber($amount);

        if ($dnType == 'F') {
          $services = ServiceChanel::select(
            'islim_services.id',
            'islim_services.description',
            'islim_services.title',
            'islim_services.price_pay',
            'islim_services.broadband',
            'islim_services.supplementary',
            'islim_fiber_service_zone.service_pk',
            'islim_periodicities.periodicity'
          )
            ->join('islim_services', 'islim_services.id', 'islim_service_channel.id_service')
            ->join('islim_fiber_service_zone', 'islim_fiber_service_zone.service_id', 'islim_services.id')          
            ->join('islim_periodicities', 'islim_periodicities.id', 'islim_services.periodicity_id')
            ->where([
              ['islim_service_channel.status', 'A'],
              ['islim_services.status', 'A'],
              ['islim_periodicities.status', 'A'],
              ['islim_services.type', 'P'],
              ['islim_services.price_pay', $amount],
              ['islim_services.service_type', $dnType],
              ['islim_fiber_service_zone.fiber_zone_id', $fiber_zone_id],
            ]);
        }else{
          $services = ServiceChanel::select(
            'islim_services.id',
            'islim_services.description',
            'islim_services.title',
            'islim_services.price_pay',
            'islim_services.broadband',
            'islim_services.supplementary',
            'islim_periodicities.periodicity'
          )
            ->join('islim_services', 'islim_services.id', 'islim_service_channel.id_service')
            ->join('islim_periodicities', 'islim_periodicities.id', 'islim_services.periodicity_id')
            ->where([
              ['islim_service_channel.status', 'A'],
              ['islim_services.status', 'A'],
              ['islim_periodicities.status', 'A'],
              ['islim_services.type', 'P'],
              ['islim_services.service_type', $dnType]]);          
        }

        //Condiciones en caso de que el dn sea acredito
        if ($typeClient == 'CR') {
          //Buscando alta del dn
          $reg = Sale::getRegisterDn($dn);

          if ($reg) {
            //Consultando elfinanciamiento del DN
            $financing = Financing::getFinancing($reg->packs_id, $reg->services_id);

            if (!empty($financing)) {
              //Filtrando por precios el servicio
              $services = $services->whereIn(
                'islim_services.price_pay',
                [
                  $amount - $financing->SEMANAL,
                  $amount - $financing->QUINCENAL,
                  $amount - $financing->MENSUAL]
              );
            }
          }
        } else {
          //Filtrando por precios el servicio
          $services = $services->where('islim_services.price_pay', $amount);
        }
        //Si el dn no pertenece a una lista se filtran los servicios por canal o por concentrador
        if (!$list) {
          $services = $services->where(function ($query) use ($conc) {
            $query->where(
              'islim_service_channel.id_channel',
              !empty($conc->id_channel) ? $conc->id_channel : 0
            )
              ->orWhere('islim_service_channel.id_concentrator', $conc->id);
          });
        } else {
          //Si el dn pertenece a una lista se filtran los servicios asociados a esa lista
          $services = $services->where('islim_service_channel.id_list_dns', $list);
        }

        if ($canBuyChHome) {
          $services = $services->whereIn('islim_services.id', explode(',', env('CHANGE_COORDS')));
        } else {
          $services = $services->whereNotIn('islim_services.id', explode(',', env('CHANGE_COORDS')));
        }
        
        //No borrar esto puede servir cuando decidan activar recargas
        /*if($dnType == 'T'){
        $services = $services->where('islim_services.id', '!=', $father)
        ->where(function($query) use ($father){
        $query->where('islim_services.primary_service', $father)
        ->orWhereNull('islim_services.primary_service');
        });
        }*/

        if ($typeClient == 'CR') {
          $services = $services->get();
          if (count($services)) {
            foreach ($services as $s) {
              if ($amount == $s->price_pay + $financing->{$s->periodicity}) {
                $service = $s;
                break;
              }
            }
          }
        } else {
          $service = $services->first();
        }

        if (!empty($service)) {
          if (Common::getLastNumber($service->broadband) <= Common::getLastNumber($serviceability) || ($dnType == 'T' || $dnType == 'M' || $dnType == 'MH' || $dnType == 'F')) {
            return $service;
          }
        }
      }
    }

    return null;
  }

  public static function getServiceFather($service = false)
  {
    if ($service) {
      $data = self::select('id', 'type', 'codeAltan', 'primary_service')
        ->where('id', $service)
        ->first();

      if (!empty($data) && $data->type == 'A') {
        $data = self::select('id')
          ->where([
            ['status', 'A'],
            ['service_type', 'T'],
            ['type', 'P'],
            ['codeAltan', $data->codeAltan]])
          ->first();

        if (!empty($data)) {
          return $data->id;
        }
      } elseif (!empty($data) && !empty($data->primary_service)) {
        return $data->primary_service;
      } elseif (!empty($data) && !empty($data->id)) {
        return $data->id;
      }
    }

    return 0;
  }

  public static function getServiceById($id = false)
  {
    if ($id) {
      return self::select(
        'islim_services.id',
        'islim_services.title',
        'islim_services.price_pay',
        'islim_services.broadband',
        'islim_services.codeAltan',
        'islim_services.type_hbb',
        'islim_periodicities.periodicity'
      )
        ->join(
          'islim_periodicities',
          'islim_periodicities.id',
          'islim_services.periodicity_id'
        )
        ->where('islim_services.id', $id)
        ->first();
    }

    return null;
  }

  public static function getServiceByIdAndZone($id = false, $dn = false)
  {
    if ($id && $dn) {
      $fiber_zone_id = ClientNetwey::getFiberZoneByDn($dn);
      return self::select(
          'islim_services.id',
          'islim_services.title',
          'islim_services.price_pay',
          'islim_services.broadband',
          'islim_services.codeAltan',
          'islim_services.type_hbb',
          'islim_fiber_service_zone.service_pk',        
          'islim_periodicities.periodicity'
        )
        ->join(
          'islim_periodicities',
          'islim_periodicities.id',
          'islim_services.periodicity_id'
        )
        ->join(
          'islim_fiber_service_zone', 
          'islim_fiber_service_zone.service_id',
          'islim_services.id')  
        ->where([
          ['islim_services.id', $id],
          ['islim_fiber_service_zone.fiber_zone_id', $fiber_zone_id]
        ])
        ->first();
    }

    return null;
  }

    /*Retorna un id de servicio que puede ser activado por un dn dado y es buscado por medio del monto*/
    public static function getAllDataServiceByAmountAndZone($dn = false, $amount = false, $dnType = false)
    {
        if ($dn && $amount && $dnType) {

            $fiber_zone_id = ClientNetwey::getFiberZoneByDn($dn);

            if (!empty($fiber_zone_id)) {

                $amount = Common::cleanNumber($amount);

                if ($dnType == 'F') {

                    $service = ServiceChanel::select(
                        'islim_services.id',
                        'islim_services.description',
                        'islim_services.title',
                        'islim_services.price_pay',
                        'islim_services.broadband',
                        'islim_services.supplementary',
                        'islim_fiber_service_zone.service_pk',
                        'islim_services.codeAltan',
                        'islim_periodicities.periodicity'
                    )
                        ->join('islim_services', 'islim_services.id', 'islim_service_channel.id_service')
                        ->join('islim_fiber_service_zone', 'islim_fiber_service_zone.service_id', 'islim_services.id')
                        ->join('islim_periodicities', 'islim_periodicities.id', 'islim_services.periodicity_id')
                        ->where([
                            ['islim_service_channel.status', 'A'],
                            ['islim_services.status', 'A'],
                            ['islim_periodicities.status', 'A'],
                            ['islim_services.type', 'P'],
                            ['islim_services.service_type', $dnType],
                            ['islim_services.price_pay', $amount],
                            ['islim_fiber_service_zone.fiber_zone_id', $fiber_zone_id],
                        ]);

                    if (!is_null($service)){

                        return $service;

                    }else{

                        Log::debug('No se definio un servicio para el DN: '.$dn.' de tipo '.$dnType.' de la zona: '.$fiber_zone_id.' por el monto '.$amount);
                        return null;

                    }
                }
            }
        }
    }
}
