<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resource_locks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
	        $table->bigInteger('user_id')->nullable()->unsigned();
            $table->morphs('lockable');

	        $table->foreign('user_id')->references('id')->on('lara_auth_users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_locks');
    }
};
