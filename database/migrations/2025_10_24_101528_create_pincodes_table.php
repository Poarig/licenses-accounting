<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('pincodes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('license_id')->constrained()->onDelete('cascade');
        $table->string('value');
        $table->enum('status', ['nonactivated', 'active', 'used'])->default('nonactivated');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pincodes');
    }
};
