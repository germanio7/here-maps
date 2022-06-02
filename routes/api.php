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

    $easytrip = json_decode(file_get_contents(base_path('easytrip.json')));
    $casetas = collect();

    foreach ($easytrip as $key => $value) {
        $casetas->push([
            'lat' => $value->Latitud,
            'lng' => $value->Longitud
        ]);
    }

    $decode = FlexiblePolyline::decode($results['routes'][0]['sections'][0]['polyline']);

    $decode = $decode['polyline'];
    $polyline = collect();

    foreach ($decode as $key => $value) {
        $polyline->push([
            'lat' => $value[0],
            'lng' => $value[1]
        ]);
    }

    $matrix = Http::post('https://matrix.router.hereapi.com/v8/matrix', [
        'apiKey'            => config('services.here.api_key'),
        'origins'           => $polyline,
        'destinations'      => $casetas,
        'regionDefinition'  => 'type:world',
    ]);

    return $matrix->json();
});

Route::post('buscar-destino', function (Request $request) {

    $params = http_build_query(
        [
            'buscar'    => $request->buscar,
            'type'      => $request->type,
            'num'       => $request->num,
            'key'       => config('services.inegi.api_key'),
        ]
    );

    $client = Http::post(config('services.inegi.base_url') . '/buscadestino?' . $params);

    return $client->json();
});

Route::post('calcular-ruta', function (Request $request) {

    $params = http_build_query(
        [
            'dest_i'    => $request->dest_i,
            'dest_f'    => $request->dest_f,
            'type'      => $request->type,
            'v'         => $request->v,
            'key'       => config('services.inegi.api_key'),
        ]
    );

    $client = Http::post(config('services.inegi.base_url') . '/cuota?' . $params);

    return $client->json();
});

Route::post('detalles-calcular-ruta', function (Request $request) {

    $params = http_build_query(
        [
            'dest_i'    => $request->dest_i,
            'dest_f'    => $request->dest_f,
            'type'      => $request->type,
            'v'         => $request->v,
            'key'       => config('services.inegi.api_key'),
        ]
    );

    $client = Http::post(config('services.inegi.base_url') . '/detalle_c?' . $params);

    return $client->json();
});
