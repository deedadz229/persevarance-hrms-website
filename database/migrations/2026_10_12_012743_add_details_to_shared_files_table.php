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
        Schema::table('shared_files', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
            $table->text('description')->nullable()->after('category');
            $table->foreignId('uploaded_by')->nullable()->after('owner_name')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shared_files', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn(['category', 'description', 'uploaded_by']);
        });
    }
};
