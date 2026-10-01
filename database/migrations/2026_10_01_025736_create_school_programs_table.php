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
        Schema::create('school_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_unit_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            // A unit shouldn't have duplicate program names
            $table->unique(['school_unit_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_programs');
    }
};
