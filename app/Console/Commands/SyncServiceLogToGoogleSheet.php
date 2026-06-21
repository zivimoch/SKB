<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetsClient;
use App\Services\ServiceLogSheetBuilder;
use Illuminate\Console\Command;
use Throwable;

class SyncServiceLogToGoogleSheet extends Command
{
    protected $signature = 'skb:sync-service-log';

    protected $description = 'Menulis ulang Log Layanan (Auto) dari database SKB ke Google Sheets.';

    public function handle(ServiceLogSheetBuilder $builder, GoogleSheetsClient $sheets): int
    {
        if (! config('google_sheets.enabled')) {
            $this->warn('Sinkronisasi Google Sheets tidak aktif.');

            return self::SUCCESS;
        }

        try {
            $rows = $builder->rows();
            $sheets->replaceServiceLogRows($rows);
            $this->info(count($rows).' baris Log Layanan berhasil disinkronkan.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
