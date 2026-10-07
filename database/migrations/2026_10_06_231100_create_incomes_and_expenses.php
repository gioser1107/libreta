<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('occurred_on');
            $table->string('concept', 160);
            $table->string('category', 40);
            $table->char('currency', 3);
            $table->decimal('amount', 14, 2);
            $table->decimal('amount_ves', 14, 2);
            $table->decimal('amount_usd', 14, 2);
            $table->decimal('rate_to_ves', 14, 6);
            $table->decimal('usd_rate_to_ves', 14, 6);
            $table->foreignId('bcv_rate_id')->constrained('bcv_rates')->restrictOnDelete();
            $table->foreignId('usd_bcv_rate_id')->constrained('bcv_rates')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'occurred_on']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('occurred_on');
            $table->string('concept', 160);
            $table->string('category', 40);
            $table->char('currency', 3);
            $table->decimal('amount', 14, 2);
            $table->decimal('amount_ves', 14, 2);
            $table->decimal('amount_usd', 14, 2);
            $table->decimal('rate_to_ves', 14, 6);
            $table->decimal('usd_rate_to_ves', 14, 6);
            $table->foreignId('bcv_rate_id')->constrained('bcv_rates')->restrictOnDelete();
            $table->foreignId('usd_bcv_rate_id')->constrained('bcv_rates')->restrictOnDelete();
            $table->string('payment_method', 32)->nullable();
            $table->string('status', 16)->default('paid');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'occurred_on']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('incomes');
    }
};
