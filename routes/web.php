<?php
use App\Http\Controllers\PayController;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
Route::middleware(['BasicAuth'])->group(function(){
	/*Request de autorización*/
	Route::post('/auth-pay', [PayController::class, 'authPay']);

	/*Request de cancelacion*/
	Route::delete('/cancel-pay', [PayController::class, 'cancelPay']);
});