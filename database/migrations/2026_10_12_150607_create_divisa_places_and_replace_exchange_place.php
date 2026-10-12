<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisa_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::table('currency_exchanges', function (Blueprint $table) {
            $table->foreignId('divisa_place_id')->nullable()->after('from_bank_id')->constrained('divisa_places')->restrictOnDelete();
        });

        $labels = [
            'binance' => 'Binance',
            'efectivo' => 'Efectivo',
        ];
        $places = [];

        foreach (DB::table('currency_exchanges')->select('id', 'user_id', 'place')->orderBy('id')->get() as $row) {
            $name = $labels[$row->place] ?? (string) $row->place;
            $key = $row->user_id.'|'.$name;

            if (! isset($places[$key])) {
                $places[$key] = DB::table('divisa_places')->insertGetId([
                    'user_id' => $row->user_id,
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('currency_exchanges')->where('id', $row->id)->update([
                'divisa_place_id' => $places[$key],
            ]);
        }

        Schema::table('currency_exchanges', function (Blueprint $table) {
            $table->dropColumn('place');
        });
    }

    public function down(): void
    {
        Schema::table('currency_exchanges', function (Blueprint $table) {
            $table->string('place', 20)->default('efectivo')->after('from_bank_id');
        });

        $names = DB::table('divisa_places')->pluck('name', 'id');

        foreach (DB::table('currency_exchanges')->select('id', 'divisa_place_id')->get() as $row) {
            $name = mb_strtolower((string) ($names[$row->divisa_place_id] ?? 'efectivo'));
            DB::table('currency_exchanges')->where('id', $row->id)->update([
                'place' => $name,
            ]);
        }

        Schema::table('currency_exchanges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('divisa_place_id');
        });

        Schema::dropIfExists('divisa_places');
    }
};
