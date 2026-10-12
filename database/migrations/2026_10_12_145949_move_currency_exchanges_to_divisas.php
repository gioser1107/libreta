<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currency_exchanges', function (Blueprint $table) {
            $table->dropForeign(['to_bank_id']);
            $table->dropColumn('to_bank_id');
        });

        Schema::table('currency_exchanges', function (Blueprint $table) {
            $table->string('place', 20)->default('efectivo')->after('from_bank_id');
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->dropColumn('is_savings');
        });
    }

    public function down(): void
    {
        Schema::table('currency_exchanges', function (Blueprint $table) {
            $table->dropColumn('place');
            $table->foreignId('to_bank_id')->after('from_bank_id')->constrained('banks')->restrictOnDelete();
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->boolean('is_savings')->default(false)->after('opening_eur');
        });
    }
};
