<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->decimal('opening_ves', 14, 2)->default(0)->after('name');
            $table->decimal('opening_usd', 14, 2)->default(0)->after('opening_ves');
            $table->decimal('opening_eur', 14, 2)->default(0)->after('opening_usd');
        });
    }

    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropColumn(['opening_ves', 'opening_usd', 'opening_eur']);
        });
    }
};
