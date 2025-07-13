<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CrimeStatistics;

class GenerateCrimeStatistics extends Command
{
    protected $signature = 'crime:generate-statistics {date?}';
    protected $description = 'Generate crime statistics for a given date';

    public function handle()
    {
        $date = $this->argument('date') ?: now()->subDay()->toDateString();
        
        $this->info("Generating crime statistics for {$date}...");
        
        CrimeStatistics::generateForDate($date);
        
        $this->info("Statistics generated successfully!");
    }
}