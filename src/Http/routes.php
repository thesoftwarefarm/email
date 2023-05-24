<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhooks routes
|--------------------------------------------------------------------------
|
| Endpoints called by third party services when a email is sent on failed
|
*/

Route::group(['namespace' => 'TsfCorp\Email\Http\Controllers'], function(){
    Route::post('/webhooks/mailgun', 'MailgunWebhookController@index');
    Route::post('/webhook-ses', 'SesWebhookController@index');
});

