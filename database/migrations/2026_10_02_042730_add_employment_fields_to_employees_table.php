<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employment_type')->default('permanent')->after('join_date');
            $table->string('special_status')->nullable()->after('status');
            $table->date('resigned_at')->nullable()->after('special_status');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'employment_type',
                'special_status',
                'resigned_at',
            ]);
        });
    }
};
