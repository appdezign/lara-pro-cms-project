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
        Schema::create('lara_form_classicforms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('mydate')->nullable();
            $table->text('myradio')->nullable();
            $table->text('myselect')->nullable();
            $table->text('mycomment')->nullable();
            $table->boolean('myboolean')->default(false);
            $table->string('myemail')->nullable();
            $table->string('myname')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('ipaddress')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable()->index('lara_form_classicforms_locked_by_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lara_form_classicforms');
    }
};
