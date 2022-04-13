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

Route::get('near', function(){
    $client = Http::get('https://places.ls.hereapi.com/places/v1/discover/here', [
        'apiKey' => config('services.here.api_key'),
        'at'     => '-27.18025,-59.33659',
    ]);
    return $client->json();
});
