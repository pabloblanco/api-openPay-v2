<?php

namespace App\Console\Commands;

use App\AltanCode;
use App\APIKey;
use App\Bash;
use App\ClientNetwey;
use App\OpenPay;
use App\Sale;
use App\Service;
use Illuminate\Console\Command;
use Log;

class ProcessCommand extends Command
{
  /**
   * The console command name.
   *
   * @var string
   */
  protected $signature = "command:payments";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Procesa los pagos y crea recargas para ser procesadas por la api de recarga.";

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle()
  {

    if (!Bash::isActive() && env('PROCESS') == 1) {
      Bash::active();

      //Consultando pagos exitosos sin procesar
      $pays = OpenPay::where('status', 'A')->get();

      Log::debug('activando proceso de recarga ' . date('Y-m-d H:i:s'));

      //Obteniendo concentrador
      $conc = APIKey::getInfConc(env('API_KEY_ALTAM'));

      foreach ($pays as $pay) {
	      $invalidService = 0 ;
        if (strtotime('+ ' . env('TIME_WAIT', 15) . ' minutes', strtotime($pay->date_update)) <= time() && !OpenPay::isProcesed($pay->id)) {
          //datos del cliente
          $client = ClientNetwey::getDnData(
            $pay->msisdn,
            [
              'msisdn',
              'type_buy',
              'serviceability',
              'price_remaining',
              'num_dues',
              'dn_type']
          );

          if ($client->dn_type == 'F'){
            //datos del servicio
            //$service = Service::getAllDataServiceByAmountAndZone($pay->msisdn, $pay->amount, $client->dn_type);
            $service = Service::getServiceByIdAndZone($pay->service_id, $pay->msisdn);
          }else{
            //datos del servicio
            $service = Service::getServiceById($pay->service_id);
          }
	        // Log::debug($pay->msisdn." ".$client->dn_type." ".$pay->service_id." ".date('Y-m-d H:i:s'));

          if ($client->dn_type == 'H') {
            $lastservice = Sale::getLastService($pay->msisdn);

            //Comentar esta linea cuando se migren todos los clientes e igualar la variable a false
            //$isMigration = Sale::isOldOffert($pay->msisdn);

            $code = AltanCode::getCode(
              $service->id,
              $service->broadband,
              $client->serviceability,
              $lastservice->type_hbb == $service->type_hbb,
              false//$isMigration
            );

            $codeAltan = $code->codeAltan;



/* lo comentado se quita por conflicto en merge
          } else {
            if ($client->dn_type == 'F'){
              $codeAltan = $service->service_pk;
            }else{
              $codeAltan = $service->codeAltan;
            }

          }

          $saleO = Sale::getSaleByTransaction($pay->unique_transaction);

          if (!empty($client) && !empty($service) && !empty($codeAltan) && empty($saleO)) {
            //Precio neto del servicio
            $amountNeto = $pay->amount - ($pay->amount * env('TAX'));

            //Calculando comision del concentrador
            $comision = round($amountNeto * $conc->commissions, 2);

            $sale                     = new Sale;
            $sale->services_id        = $pay->service_id;
            $sale->concentrators_id   = $conc->id;
            $sale->api_key            = env('API_KEY_ALTAM');
            $sale->unique_transaction = $pay->unique_transaction;
            $sale->type               = 'R';
            $sale->id_point           = 'OPEN_PAY';
            $sale->description        = $service->title;
            $sale->amount             = $pay->amount;
            $sale->amount_net         = $amountNeto;
            $sale->com_amount         = $comision;
            $sale->msisdn             = $pay->msisdn;
            $sale->conciliation       = 'N';
            $sale->date_reg           = date('Y-m-d H:i:s');
            $sale->status             = 'EC';
            $sale->codeAltan          = $codeAltan;
            $sale->sale_type          = $client->dn_type;
            $sale->save();

            Log::debug('procesando recarga dn: ' . $pay->msisdn . ' unique: ' . $pay->unique_transaction . ' ' . date('Y-m-d H:i:s'));

            //Actualizando credito
            if ($client->type_buy == 'CR') {
              ClientNetwey::updateCredit($client, $service, $sale->id);
**************************************************************************/






          }else{
            if ($client->dn_type == 'F'){
//	            if(empty($service->service_pk)){
//                    $txt0 = "servicio invalido para el cliente :".$pay->msisdn." tipo:".$client->dn_type." servicio:".$pay->service_id;
//                    Log::debug($txt0 . date('Y-m-d H:i:s'));
//                    $invalidService = 1;
//                }else
                    $codeAltan = $service->service_pk;
            }else{
              $codeAltan = $service->codeAltan;
            }

          }

          if($invalidService == 0){
          	$saleO = Sale::getSaleByTransaction($pay->unique_transaction);

          	if (!empty($client) && !empty($service) && !empty($codeAltan) && empty($saleO)) {
            		//Precio neto del servicio
            		$amountNeto = $pay->amount - ($pay->amount * env('TAX'));

            		//Calculando comision del concentrador
            		$comision = round($amountNeto * $conc->commissions, 2);

            		$sale                     = new Sale;
            		$sale->services_id        = $pay->service_id;
            		$sale->concentrators_id   = $conc->id;
            		$sale->api_key            = env('API_KEY_ALTAM');
            		$sale->unique_transaction = $pay->unique_transaction;
            		$sale->type               = 'R';
            		$sale->id_point           = 'OPEN_PAY';
            		$sale->description        = $service->title;
            		$sale->amount             = $pay->amount;
            		$sale->amount_net         = $amountNeto;
            		$sale->com_amount         = $comision;
            		$sale->msisdn             = $pay->msisdn;
            		$sale->conciliation       = 'N';
            		$sale->date_reg           = date('Y-m-d H:i:s');
            		$sale->status             = 'EC';
            		$sale->codeAltan          = $codeAltan;
            		$sale->sale_type          = $client->dn_type;
            		$sale->save();

            		Log::debug('procesando recarga dn: ' . $pay->msisdn . ' unique: ' . $pay->unique_transaction . ' ' . date('Y-m-d H:i:s'));

            		//Actualizando credito
            		if ($client->type_buy == 'CR') {
              		  ClientNetwey::updateCredit($client, $service, $sale->id);
            		}

            		$pay->status = 'P';
          	}elseif(empty($sale)){
            		$pay->status = 'E';
          	}
      	  }else{
      		  $pay->status = 'E';
      	  }
          $pay->save();
          $this->info('Recarga revisada por cron. ID: ' . $pay->id) . PHP_EOL;
        } else {
          $this->info("Espera 15 min") . PHP_EOL;
        }
      }
      $txt0 = 'apagando proceso de recarga ';
      $this->info($txt0) . PHP_EOL;
      Log::debug($txt0 . date('Y-m-d H:i:s'));
      Bash::inactive();
    } else {
      $this->info("Proceso de cron bloqueado") . PHP_EOL;
    }
  }
}
