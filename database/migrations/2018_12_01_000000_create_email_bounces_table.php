<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailBouncesTable extends Migration
{
    public function up()
    {
        Schema::create('email_bounces', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('email_id')->unsigned()->index();
            $table->string('recipient')->nullable();
            $table->string('code')->nullable();
            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_bounces');
    }
}