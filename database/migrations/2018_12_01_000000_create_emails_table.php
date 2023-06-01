<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailsTable extends Migration
{
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('project')->nullable()->index();
            $table->text('from');
            $table->string('subject')->nullable()->index();
            $table->text('attachments')->nullable();
            $table->text('reply_to')->nullable();
            $table->longText('body')->nullable();
            $table->string('provider')->default('mailgun')->index();
            $table->enum('status', ['pending', 'queued', 'sent', 'failed'])->default('pending')->index();
            $table->integer('retries')->default(0)->index();
            $table->string('remote_identifier')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('emails');
    }
}
