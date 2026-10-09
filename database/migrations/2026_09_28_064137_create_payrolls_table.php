<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');

            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('total_allowance', 15, 2)->default(0);
            $table->decimal('total_deduction', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);

            $table->string('status')->default('draft');

            $table->timestamp('generated_at')->nullable();

            $table->timestamps();

            $table->unique([
                'employee_id',
                'period_month',
                'period_year',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
