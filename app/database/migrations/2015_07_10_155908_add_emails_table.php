<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEmailsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emails', function(Blueprint $t)
        {
            // auto increment id (primary key)
            $t->increments('id');

            $t->string('account');
            $t->string('google_id');

            $t->string('sender');
            $t->string('receiver');
            $t->string('sender_fullname');
            $t->string('receiver_fullname');
            $t->string('subject');
            $t->text('content');
            $t->string('tstamp');

            $t->string('category'); // inbox / sent
            $t->boolean('fav');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('emails');

    }

}
