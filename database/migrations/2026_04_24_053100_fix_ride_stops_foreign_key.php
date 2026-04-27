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
        Schema::table('ride_stops', function (Blueprint $table) {
            // Drop existing foreign key
            $table->dropForeign(['ride_id']);
            
            // Add correct foreign key to rides table
            $table->foreign('ride_id')->references('id')->on('rides')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ride_stops', function (Blueprint $table) {
            // Drop correct foreign key
            $table->dropForeign(['ride_id']);
            
            // Revert to wrong foreign key (for rollback)
            $table->foreign('ride_id')->references('id')->on('carpool_rides')->onDelete('cascade');
        });
    }
};
