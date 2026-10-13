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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');
            $table->string('name');
            $table->dateTime('event_date');
            $table->text('desc')->nullable();
            $table->string('status');
            $table->string('code')->nullable();
            $table->integer('max_capacity');

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
        Schema::dropIfExists('events');
    }
};
