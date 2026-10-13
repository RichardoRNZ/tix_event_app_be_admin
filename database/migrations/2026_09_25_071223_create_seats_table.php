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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('no');
            
            // Audit columns
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps(); // created_at, updated_at
            $table->string('deleted_by')->nullable();
            $table->softDeletes(); // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
