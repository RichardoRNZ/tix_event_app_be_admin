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
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('desc')->nullable();
            $table->string('location')->nullable(); // Changed from point to string to avoid BadMethodCallException
            $table->text('address')->nullable();
            
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
        Schema::dropIfExists('venues');
    }
};
