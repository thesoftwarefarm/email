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

Route::namespace('TsfCorp\Email\Http\Controllers')->group(function (){
    Route::post('/webhook-mailgun', 'MailgunWebhookController@index');
});
