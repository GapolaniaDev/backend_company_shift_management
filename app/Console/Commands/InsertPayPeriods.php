<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InsertPayPeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payperiods:insert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert pay periods into the table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = now();
        $endDate = now()->addDays(14); // Ejemplo: dos semanas
        $this->info('assas');

        DB::table('pay_periods')->insert([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_at' => now(),
            'updated_at' => now(),
            'fiscal_week' => 10
        ]);

        $this->info('Pay periods inserted successfully.');

        return 0;
    }
}
