<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol', 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('currencies')->insert([
            ['code' => 'VES', 'name' => 'Bolívar', 'symbol' => 'Bs', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'USD', 'name' => 'Dólar estadounidense', 'symbol' => '$', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::create('bcv_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->decimal('rate_to_ves', 14, 6);
            $table->date('date_effective');
            $table->timestamps();

            $table->unique(['currency_id', 'date_effective']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bcv_rates');
        Schema::dropIfExists('currencies');
    }
};
