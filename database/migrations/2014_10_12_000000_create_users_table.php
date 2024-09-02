<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name', 255)->required();
            $table->string('last_name', 255)->required();
            $table->string('phone', 56)->nullable();
            $table->string('email', 191)->required();
            $table->string('address', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->string('state', 255)->nullable();
            $table->string('country', 255)->nullable();
            $table->integer('zip')->nullable();
            $table->string('password', 255)->required();
            $table->date('dob')->nullable();
            $table->date('doj')->nullable();
            $table->string('photo', 255)->nullable();
            $table->string('avatar', 255)->default('avatar.png');
            $table->tinyInteger('active_status')->default(0)->comment('For chatify messenger');
            $table->tinyInteger('dark_mode')->default(0);
            $table->string('messenger_color', 255)->nullable();
            $table->string('lang', 28)->default('en');
            $table->text('remember_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->tinyInteger('status')->default(0);
            $table->text('country_code')->nullable();
            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
