<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Slack;
use Illuminate\Support\Facades\Log;

class Model_islim_table_logs extends Model
{
    use HasFactory;
  protected $table = 'islim_table_logs';

  protected $fillable = [
    'id',                   // id autoincrementado. int(11)
    'data_send',            // Data enviada al curl. longtext
    'data_return',          // Data recibida del curl. longtext
    'request',              // Request externo. varchar(255)
    'request_intermedio',   // Request intermedio. varchar(255)
    'time',                 // Seg en dar respuesta. Double
    'date_reg',             // Fecha que se registro. DateTime
    'ip',                   // ip de la peticion. varchar
    'token',                // token usado. varchar(255)
    'header',               // header enviado. longtext
    'type'];                // enum('OK','ERROR')

  public $timestamps = false;

  /**
   * Metodo para seleccionar conexion a la bd, escritura-lectura o solo escritura
   * @param String $typeCon
   *
   * @return App\models\Model_islim_table_logs
   */
  public static function getConnect($typeCon = false)
  {
    if ($typeCon) {
      $obj = new self;
      $obj->setConnection($typeCon == 'W' ? 'mysql::write' : 'mysql::read');

      return $obj;
    }
    return null;
  }

 public static function saveLogBD($request = false, $data_send = false, $data_return = false, $time = false, $type = false, $error_details = false)
  {
    if ($type == 'INFO') {
      Log::info($error_details);
      return 0;
    }

    $log = self::getConnect('W');
    if ($request) {

      $log->ip                 = $request->ip();
      $log->request            = '[' . strtoupper($request->method()) . ']' . $request->url();
      $log->request_intermedio = '[' . strtoupper($request->methodIntermedia()) . ']' . $request->urlIntermedia();

      $log->token  = $request->bearerToken();
      $log->header = (String) json_encode($request->header());
    }

    $log->data_send   = !empty($data_send) ? (String) json_encode($data_send) : null;
    $log->data_return = !empty($data_return) ? (String) json_encode($data_return) : null;
    $log->time        = !empty($time) ? round($time, 3) : 0;
    if ($type != 'OK') {
      $log->type = 'ERROR';
    } else {
      $log->type = $type;
    }
    $log->date_reg = date('Y-m-d H:i:s');

    $log->save();

    if ($type != 'OK') {
      if ($type == 'FAIL' || $type == 'ERROR') {
        $type = 'ALERT';
      }
      if ($request) {
        Slack::sendSlackNotification($error_details, $type, $data_send, $request, $data_return, $log->time);
      } elseif ($type == 'ERROR') {
        Log::error($error_details);
      } elseif ($type == 'WARNING') {
        Log::warning($error_details);
      } elseif ($type == 'ALERT') {
        Log::alert($error_details);
      } elseif ($type == 'CRITICAL') {
        Log::critical($error_details);
      } elseif ($type == 'EMERGENCY') {
        Log::emergency($error_details);
      } else {
        Log::notice($error_details);
      }
    }
  }
}
