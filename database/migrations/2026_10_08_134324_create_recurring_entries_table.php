<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('concept', 160);
            $table->string('category', 40);
            $table->char('currency', 3);
            $table->decimal('amount', 14, 2);
            $table->foreignId('bank_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('payment_method', 32)->nullable();
            $table->string('status', 16)->default('paid');
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('day_of_month');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'active']);
        });

        Schema::create('recurring_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_entry_id')->constrained()->cascadeOnDelete();
            $table->char('period', 7);
            $table->foreignId('income_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['recurring_entry_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_posts');
        Schema::dropIfExists('recurring_entries');
    }
};
