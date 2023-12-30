<?php
namespace App\Helpers;
/*
Septiembre 2022
 */
use App\Models\Model_islim_table_logs;
use App\Models\Model_islim_table_tokenLife;
use App\Models\Request_curl;

class Curl
{
  public function __construct()
  {
    date_default_timezone_set('America/Mexico_City');
  }
  /**
   * [executeCurl description]
   * @param  boolean $url     [url o request a ejecutar]
   * @param  boolean $type    [tipo  de peticion: Get o Post]
   * @param  array   $header  [cabecera de la peticion]
   * @param  array   $data    [data enviada a 99min]
   * @param  [type]  $id_card [id del carrito asociado a la peticion]
   * @return [Array]           [resultado del request]
   */
  public static function executeCurl($url = false, $type = false, $header = [], $data = [], $request)
  {
    if ($url && $type) {
      $startTime   = microtime(true);
      $SendRequest = true;
      $timeToke    = '';
      $curl        = curl_init();

      if (!count($header)) {

        if (strcmp($url, 'oauth/token') === 0) {
          $header = [
            "accept: */*",
            "Content-Type: application/json",
            "cache-control: no-cache",
            "accept-language: en-US,en;q=0.8",
          ];
        } else {
          $timeToke = Model_islim_table_tokenLife::getToken($request);
          if (!empty($timeToke)) {
            $header = [
              "Content-Type: application/json",
              "Authorization: " . $timeToke->tokenType . " " . $timeToke->token,
            ];
          } else {
            $SendRequest = false;
            $DataReturn  = [
              'success' => false,
              'data'    => "No se pudo obtener Token de 99min",
              'code'    => 400,
            ];
          }
        }
      }

      if ($SendRequest) {
        if (env('APP_ENV', 'local') == 'local') {
          //Deshabilito el ssl
          curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
          curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $options = [
          CURLOPT_URL            => env('URL_API_EXTERNA') . $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING       => "",
          CURLOPT_MAXREDIRS      => 10,
          CURLOPT_TIMEOUT        => 60,
          CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST  => $type,
          CURLOPT_HTTPHEADER     => $header,
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_SSL_VERIFYHOST => false,
        ];

        if (is_array($data) && count($data) && $type == 'POST') {
          $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err      = curl_error($curl);

        curl_close($curl);
        $endTime = round((microtime(true) - $startTime), 2);

        $DataReturn = array();
        if ($err) {
          $DataReturn = [
            'success' => false,
            'data'    => $err,
            'code'    => !empty($httpcode) ? $httpcode : 0,
          ];
        } else {
          $dataJson = json_decode($response);

          if (!empty($dataJson)) {
            $DataReturn = [
              'success'  => true,
              'data'     => $dataJson,
              'original' => $response,
              'code'     => !empty($httpcode) ? $httpcode : 0,
            ];
          } else {
            $DataReturn = [
              'success'  => false,
              'data'     => 'No se pudo obtener json.',
              'original' => $response,
              'code'     => !empty($httpcode) ? $httpcode : 0,
            ];
          }
        }
      } else {
        //Fue un fallo del obtencion de token de 99min
        curl_close($curl);
        $endTime = round((microtime(true) - $startTime), 2);
      }
      $typeE = 'OK';
      $error = null;
      if (!$DataReturn['success']) {
        $typeE = 'ERROR';
        $error = $DataReturn['data'];
      }

      $requestTo = new Request_curl;
      $requestTo->setIp($request->ip());
      $requestTo->setBearerToken($request->bearerToken());
      $requestTo->setMethod($type);
      $requestTo->setUrl(env('URL_API_EXTERNA') . $url);
      $requestTo->setMethodIntermedia($request->method());
      $requestTo->setUrlIntermedia($request->url());

      if (strcmp($url, 'oauth/token') !== 0) {
        $requestTo->setHeader($header);
        $requestTo->setPath(env('APP_URL'));

      } else {
        $requestTo->setHeader($request->header());
        $requestTo->setPath(env('URL_API_EXTERNA'));
      }
      Model_islim_table_logs::saveLogBD($requestTo, $data, $dataJson, $endTime, $typeE, $error);

      return $DataReturn;
    }
    return ['success' => false, 'data' => 'Faltan datos.'];
  }
}
