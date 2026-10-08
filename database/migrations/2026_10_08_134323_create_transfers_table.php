<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_bank_id')->constrained('banks')->restrictOnDelete();
            $table->foreignId('to_bank_id')->constrained('banks')->restrictOnDelete();
            $table->date('occurred_on');
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
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
