<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JurnalUmum;
use App\Services\FinancialReportService;
use Carbon\Carbon;

class ProcessHistoricalFinancialReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financial:process-historical {--from-year=} {--to-year=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process historical financial reports data and save to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing historical financial reports...');

        $financialReportService = new FinancialReportService();

        // Get date range from options or use data range from jurnal_umum
        $fromYear = $this->option('from-year');
        $toYear = $this->option('to-year');

        if (!$fromYear || !$toYear) {
            // Get min and max dates from jurnal_umum
            $minDate = JurnalUmum::min('tanggal_bayar');
            $maxDate = JurnalUmum::max('tanggal_bayar');

            if (!$minDate || !$maxDate) {
                $this->error('No transaction data found in jurnal_umum table.');
                return 1;
            }

            $fromYear = $fromYear ?: Carbon::parse($minDate)->year;
            $toYear = $toYear ?: Carbon::parse($maxDate)->year;
        }

        $this->info("Processing from year {$fromYear} to {$toYear}");

        $totalMonths = 0;
        $processedMonths = 0;

        // Count total months to process
        for ($year = $fromYear; $year <= $toYear; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $totalMonths++;
            }
        }

        $progressBar = $this->output->createProgressBar($totalMonths);
        $progressBar->start();

        // Process each month
        for ($year = $fromYear; $year <= $toYear; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                try {
                    $financialReportService->updateFinancialReports($month, $year);
                    $processedMonths++;
                } catch (\Exception $e) {
                    $this->error("\nError processing {$year}-{$month}: " . $e->getMessage());
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();

        $this->newLine();
        $this->info("Successfully processed {$processedMonths} months of financial reports.");

        return 0;
    }
}
