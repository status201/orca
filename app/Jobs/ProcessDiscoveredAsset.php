<?php

namespace App\Jobs;

use App\Models\Asset;
use App\Services\S3Service;
use App\Services\RekognitionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDiscoveredAsset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes per asset
    public $tries = 3;

    public function __construct(
        public int $assetId
    ) {}

    public function handle(S3Service $s3Service, RekognitionService $rekognitionService): void
    {
        $asset = Asset::find($this->assetId);

        if (!$asset) {
            Log::error("ProcessDiscoveredAsset: Asset {$this->assetId} not found");
            return;
        }

        try {
            // Step 1: Extract image dimensions if not set
            if ($asset->isImage() && (!$asset->width || !$asset->height)) {
                $dimensions = $s3Service->extractImageDimensions($asset->s3_key, $asset->mime_type);
                if ($dimensions) {
                    $asset->update($dimensions);
                }
            }

            // Step 2: Generate thumbnail
            $thumbnailKey = $s3Service->generateThumbnail($asset->s3_key);
            if ($thumbnailKey) {
                $asset->update(['thumbnail_s3_key' => $thumbnailKey]);
            }

            // Step 3: Run AI tagging if enabled
            if (config('services.aws.rekognition_enabled') && $asset->isImage()) {
                Log::info("ProcessDiscoveredAsset: Running AI tagging for asset {$asset->id}");
                $labels = $rekognitionService->autoTagAsset($asset);
                Log::info("ProcessDiscoveredAsset: Generated " . count($labels) . " AI tags for asset {$asset->id}");
            }

            Log::info("ProcessDiscoveredAsset: Successfully processed asset {$asset->id}");

        } catch (\Exception $e) {
            Log::error("ProcessDiscoveredAsset: Failed for asset {$asset->id}: " . $e->getMessage());
            throw $e; // Re-throw to trigger job retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessDiscoveredAsset: Job permanently failed for asset {$this->assetId}: " . $exception->getMessage());
    }
}
