<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('wifi_verified')->default(false)->after('face_status');
            $table->string('wifi_ssid')->nullable()->after('wifi_verified');
            $table->string('ip_address')->nullable()->after('wifi_ssid');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'wifi_verified',
                'wifi_ssid',
                'ip_address',
            ]);
        });
    }
};
