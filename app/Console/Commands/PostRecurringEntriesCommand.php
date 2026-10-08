<?php

namespace App\Console\Commands;

use App\Services\Ledger\PostRecurringEntries;
use Illuminate\Console\Command;

class PostRecurringEntriesCommand extends Command
{
    protected $signature = 'ledger:post-recurring';

    protected $description = 'Anota los ingresos y egresos fijos que ya corresponden este mes';

    public function handle(PostRecurringEntries $poster): int
    {
        $posted = $poster->postDue(now('America/Caracas'));
        $this->info($posted.' movimientos fijos anotados.');

        return self::SUCCESS;
    }
}
