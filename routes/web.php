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
