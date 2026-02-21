<?php

namespace App\Console\Commands;

use App\Jobs\VerifyAssetIntegrity as VerifyAssetIntegrityJob;
use App\Models\Asset;
use Illuminate\Console\Command;

class VerifyAssetIntegrity extends Command
{
    protected $signature = 'assets:verify-integrity';

    protected $description = 'Verify all assets still exist on S3';

    public function handle(): int
    {
        $assetIds = Asset::pluck('id');

        if ($assetIds->isEmpty()) {
            $this->info('No assets to verify.');

            return Command::SUCCESS;
        }

        foreach ($assetIds as $assetId) {
            VerifyAssetIntegrityJob::dispatch($assetId);
        }

        $this->info("Dispatched {$assetIds->count()} integrity check(s).");

        return Command::SUCCESS;
    }
}
