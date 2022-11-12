<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {

    $trx = [];
    for ($i = 0; $i < 10; $i++) {
        $trx[] = ['trxid' => \Illuminate\Support\Str::uuid()->toString(), 'external_trx_time' => now()->toDateTimeString()];
    }

    foreach ($trx as $item) {
        \App\Models\Transaction::create($item);
    }

    return \App\Models\Transaction::all();
    return response()->json([
        'message' => "It's Work",
        'data' => [
            'url' => route('telenjar.index'),
            'server' => [
                'inquiry' => config('setting.url.inquiry'),
                'payment' => config('setting.url.payment'),
                'purchase' => config('setting.url.purchase')
            ],
        ]
    ]);
//    return view('welcome');
});
