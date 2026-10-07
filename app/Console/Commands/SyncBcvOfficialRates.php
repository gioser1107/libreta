<?php

namespace App\Console\Commands;

use App\Services\BcvOfficialRatesSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncBcvOfficialRates extends Command
{
    protected $signature = 'bcv:sync-official {--date= : Fecha efectiva Y-m-d (por defecto hoy)}';

    protected $description = 'Sincroniza tasas USD/EUR (VES) desde bcv.org.ve hacia bcv_rates (--date efectiva; el schedule nocturno usa el día siguiente en Caracas)';

    public function handle(BcvOfficialRatesSyncService $sync): int
    {
        $date = $this->option('date') ? (string) $this->option('date') : null;
        $dt = $date ? Carbon::parse($date) : now();

        $result = $sync->syncForDate($dt);

        foreach ($result['errors'] as $err) {
            $this->warn($err);
        }

        if ($result['usd'] !== null) {
            $this->info('USD VES: '.$result['usd']);
        }
        if ($result['eur'] !== null) {
            $this->info('EUR VES: '.$result['eur']);
        }

        if ($result['usd'] === null || $result['eur'] === null) {
            $this->error('Faltó la tasa EUR o USD. No se considera el sync completo.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
