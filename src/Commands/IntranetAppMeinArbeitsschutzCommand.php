<?php

namespace Hwkdo\IntranetAppMeinArbeitsschutz\Commands;

use Illuminate\Console\Command;

class IntranetAppMeinArbeitsschutzCommand extends Command
{
    public $signature = 'intranet-app-mein-arbeitsschutz';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
