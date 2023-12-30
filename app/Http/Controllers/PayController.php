<?php

namespace App\Http\Controllers;

use App\Models\APIKey;
use App\Models\ClientNetwey;
use App\Models\Concentrator;
use App\Models\Mobility;
use App\Models\OpenPay;
use App\Models\Sale;
use App\Models\Service;
use App\Models\Suspend;
use App\Utilities\Altan;
use App\Utilities\Common;
use Illuminate\Http\Request;
use Log;

class PayController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    //
  }

  /*Metodo para autorizar un pago por una referencia*/
  public function authPay(Request $request)
  {
    //Obteniendo campos
    $data = $request->input();

    //Repuesta que se retornara en caso de que no cumpla con ninguna condición de las definidas mas abajo
    $response = [
      'response_code'     => 96,
      'error_description' => 'Ocurrió un error en el servicio'];

    //Válidando que vengan todos los datos.
    if (!empty($data['folio']) && !empty($data['local_date']) && !empty($data['amount']) && !empty($data['trx_no'])) {
      //Obteniendo DN.
      $dn = Common::getNumberFromReference($data['folio']);

      //Creando registro de transaccion
      $opp              = new OpenPay;
      $opp->folio       = $data['folio'];
      $opp->msisdn      = $dn;
      $opp->local_date  = date('Y-m-d H:i:s', strtotime($data['local_date']));
      $opp->trx_no      = $data['trx_no'];
      $opp->amount      = $data['amount'];
      $opp->date_reg    = date('Y-m-d H:i:s');
      $opp->date_update = date('Y-m-d H:i:s');
      $opp->status      = 'C';
      $opp->save();

      //Validando DN
      $client = ClientNetwey::getDnData($dn,
        [
          'msisdn',
          'type_buy',
          'total_debt',
          'lat',
          'lng',
          'serviceability',
          'id_list_dns',
          'service_id',
          'dn_type',
          'n_update_coord',
          'status']);

      if (!empty($client)) {
        //Consultando profile
        if ($client->dn_type != 'F') {
          if (env('PROFILE', 0) == 1) {
            $prof = Altan::getQuickProfile($client->msisdn);
          } else {
            $prof = Altan::getProfile($client->msisdn);
          }
        } else {
          //Fibra
          $prof         = new \stdClass;
          $prof->status = 'active';
        }

        $canBuyChHome  = Mobility::canBuyChangeHome($client);
        $canBuySuspend = Suspend::canBuySuspend($client);
        
        //Quitar $client->dn_type == 'H' para autorizar recargas telefonicas
        if (($client->dn_type == 'H' || $client->dn_type == 'F') && !empty($prof) &&
          (
            strtolower($prof->status) == 'active' ||
            (strtolower($prof->status) == 'suspend' && ($canBuyChHome || $canBuySuspend))
          ) /*&&
        (empty($prof->plan) || strtolower($prof->plan) != 'default')*/
        ) {
          //Comprobando estatus del credito en caso de ser cliente a credito
          if ($client->type_buy == 'CR') {
            //Obteniendo total pagado del credito
            $totalPay = Sale::getTotalPayment($client->msisdn);

            if ($client->total_debt <= $totalPay) {
              $client->type_buy = 'CO';
            }

          }

          //Consultando servicialidad
          if ($client->dn_type == 'H') {
            $serv = Altan::getServiceability($client->lat, $client->lng);

            if ($serv) {
              $client->serviceability = $serv;
            }
          }

          $idServiceF = false;
          //No borrar esto puede servir cuando decidan activar recargas
          /*if($client->dn_type == 'T'){
          $idServiceF = Service::getServiceFather($client->service_id);
          }*/

          if ($client->dn_type == 'F') {
            //Obteniendo el servicio en base al precio
            $service = Service::getServiceByAmountAndZone(
              $client->msisdn,
              $data['amount'],
              $client->serviceability,
              $client->type_buy,
              $client->id_list_dns,
              $client->dn_type,
              $idServiceF,
              $canBuyChHome
            );
          }else{
            //Obteniendo el servicio en base al precio
            $service = Service::getServiceByAmount(
              $client->msisdn,
              $data['amount'],
              $client->serviceability,
              $client->type_buy,
              $client->id_list_dns,
              $client->dn_type,
              $idServiceF,
              $canBuyChHome
            );
          }

          if (!empty($service)) {
            //Obteniendo concentrador
            $conc = APIKey::getInfConc(env('API_KEY_ALTAM'));

            //Verificando que el concentrador tenga saldo
            if ($conc->balance >= $data['amount']) {
              //Obteniendo codigo de autorización
              $authN = OpenPay::getAuthCod();

              //Marcando como aprobado el pago
              $opp->authorization      = $authN;
              $opp->unique_transaction = $conc->id . uniqid() . time();
              $opp->service_id         = $service->id;
              $opp->status             = 'A';
              $opp->date_update        = date('Y-m-d H:i:s');
              $opp->save();

              //Actualizando saldo del concentrador
              Concentrator::where('id', $conc->id)
                ->update(['balance' => ($conc->balance - $data['amount'])]);

              $response = ['response_code' => 0, 'authorization_number' => $authN];
            } else {
              $response = ['response_code' => 30, 'error_description' => 'Concentrador sin saldo.'];
            }
          } else {
            $response = ['response_code' => 12, 'error_description' => 'No se consiguio el servicio.'];
          }
        } else {
          $response = ['response_code' => 12, 'error_description' => 'El DN no puede realizar recargas.'];
        }
      } else {
        $response = ['response_code' => 12, 'error_description' => 'Cliente no registrado.'];
      }
    } else {
      $response = ['response_code' => 30, 'error_description' => 'Faltan datos para procesar la recarga.'];
    }

    if (env('SAVE_LOG', true)) {
      Log::debug('data-authPay: ', $data);
      if ($response['response_code'] != 0) {
        Log::debug('data-authPay(Error): ', $response);
      }

    }

    return response()->json($response);
  }

  /*Metodo para cancelar un pago*/
  public function cancelPay(Request $request)
  {
    //Obteniendo campos
    $data = $request->input();

    if (!empty($data['folio']) && !empty($data['local_date']) && !empty($data['amount']) && !empty($data['trx_no']) && !empty($data['authorization_number'])) {
      //Marcando como cancelada la transaccion
      $trans = OpenPay::where([
        ['authorization', $data['authorization_number']],
        ['folio', $data['folio']],
        ['status', 'A'],
      ])
        ->first();

      if (!empty($trans)) {
        $trans->status      = 'R';
        $trans->date_update = date('Y-m-d H:i:s');
        $trans->save();

        //Obteniendo concentrador
        $conc = APIKey::getInfConc(env('API_KEY_ALTAM'));

        //Actualizando saldo del concentrador
        Concentrator::where('id', $conc->id)
          ->update(['balance' => ($conc->balance + $trans->amount)]);
      } else {
        $error = 'No se consiguio la transaccion.';
      }
    } else {
      $error = 'Faltan datos en el request.';
    }

    if (env('SAVE_LOG', true)) {
      Log::debug('data-cancelPay: ', $data);

      if (!empty($error)) {
        Log::debug('data-cancelPay(error): ' . $error);
      }
    }

    return '';
  }
}
