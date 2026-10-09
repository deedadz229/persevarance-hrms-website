<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();

            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->string('direction');
            $table->string('status');

            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamp('synced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
