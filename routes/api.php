<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('search-place', function(Request $request){
    $client = Http::get(config('services.here.search.base_url'), [
        'apiKey'            => config('services.here.api_key'),
        'q'                 => $request->q,
    ]);
    return $client->json();
});

Route::get('calculate-route', function (Request $request) {
    $client = Http::get(config('services.here.routing.base_url'), [
        'apiKey'            => config('services.here.api_key'),
        'transportMode'     => $request->transportMode,
        'origin'            => $request->origin,
        'destination'       => $request->destination,
        'return'            => $request->return,
        'routingMode'       => $request->routingMode,
        'lang'              => $request->lang,
        'currency'          => $request->currency,
    ]);
    return $results = $client->json();

    $string = $results['routes'][0]['sections'][0]['polyline'];

    $points = array();
    $precision = 5;
    $index = $i = 0;
    $previous = array(0, 0);
    while ($i < strlen($string)) {
        $shift = $result = 0x00;
        do {
            $bit = ord(substr($string, $i++)) - 63;
            $result |= ($bit & 0x1f) << $shift;
            $shift += 5;
        } while ($bit >= 0x20);

        $diff = ($result & 1) ? ~($result >> 1) : ($result >> 1);
        $number = $previous[$index % 2] + $diff;
        $previous[$index % 2] = $number;
        $index++;
        $points[] = $number * 1 / pow(10, $precision);
    }

    return is_array($points) ? array_chunk($points, 2) : array();
    // return $points;
});
