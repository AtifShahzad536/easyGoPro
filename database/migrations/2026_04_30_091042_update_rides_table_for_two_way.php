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
        Schema::table('rides', function (Blueprint $table) {
            $table->enum('booking_type', ['standard', 'two_way'])->default('standard')->after('ride_type');
            $table->timestamp('scheduled_at')->nullable()->after('booking_type');
            $table->unsignedBigInteger('linked_ride_id')->nullable()->after('scheduled_at');
            $table->boolean('is_return')->default(false)->after('linked_ride_id');

            $table->foreign('linked_ride_id')->references('id')->on('rides')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropForeign(['linked_ride_id']);
            $table->dropColumn(['booking_type', 'scheduled_at', 'linked_ride_id', 'is_return']);
        });
    }
};
