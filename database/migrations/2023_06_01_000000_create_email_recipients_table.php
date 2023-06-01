<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailRecipientsTable extends Migration
{
    public function up()
    {
        Schema::create('email_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_id')->index();
            $table->enum('type', ['to', 'cc', 'bcc'])->index();
            $table->string('email')->index();
            $table->string('name')->nullable()->index();
            $table->enum('status', ['delivered', 'failed'])->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_recipients');
    }
}
