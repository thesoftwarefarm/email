<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableEmailsAddReplyTo extends Migration
{
    public function up()
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->text('reply_to')->after('to');
        });
    }

    public function down()
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn('reply_to');
        });
    }
}
