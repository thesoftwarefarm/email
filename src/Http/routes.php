<?php

use Illuminate\Support\Facades\Route;
use TsfCorp\Email\Http\Controllers\MailgunWebhookController;
use TsfCorp\Email\Http\Controllers\SesWebhookController;

Route::post('/webhook-mailgun', [MailgunWebhookController::class, 'index']);
Route::post('/webhook-ses', [SesWebhookController::class, 'index']);
