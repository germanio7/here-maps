<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Heremaps\FlexiblePolyline\FlexiblePolyline;

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

Route::get('search-place', function (Request $request) {
    $client = Http::get(config('services.here.search.base_url'), [
        'apiKey'            => config('services.here.api_key'),
        'q'                 => $request->q,
        'in'                => $request->in,
        'at'                => $request->at,
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

    // return FlexiblePolyline::decode($results['routes'][0]['sections'][0]['polyline']);
});
