<?php

namespace App\Http\Middleware;

use App\Models\Model_islim_table_ips;
use App\Models\Model_islim_table_logs;
use Closure;
use Illuminate\Http\Request;

class IPauthorized
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
   * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
   */
  public function handle(Request $request, Closure $next)
  {
    if (Model_islim_table_ips::isIpValid($request->ip())) {
      return $next($request);
    } else {
      $msg = 'Intento de conexion desde: ' . $request->ip();
      Model_islim_table_logs::saveLogBD(false, false, false, false, 'INFO', $msg);

      return response()
        ->json([
          'success' => false,
          'data' => [
            'cod_err' => 'ORG_NP',
            'msg' => 'Origen no autorizado.',
          ],
        ], 401);
    }
  }
}
