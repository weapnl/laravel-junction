<?php

namespace Weap\Junction\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Weap\Junction\Models\MediaTemporaryUpload;

class CleanMediaTemporaryUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:clean-media-temporary-uploads {hours=24 : (optional) Records older than this number of hours will be cleaned.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if (! class_exists(Media::class)) {
            return static::FAILURE;
        }

        $maxAgeInHours = $this->argument('hours');
        $cutOffDate = Carbon::now()->subHours((int) $maxAgeInHours)->format('Y-m-d H:i:s');

        $mediaTemporaryUploads = MediaTemporaryUpload::query()
            ->where('created_at', '<', $cutOffDate)
            ->get();

        foreach ($mediaTemporaryUploads as $mediaTemporaryUpload) {
            $mediaTemporaryUpload->forceDelete();
        }

        $this->info("Deleted {$mediaTemporaryUploads->count()} record(s) from the media temporary uploads.");

        return static::SUCCESS;
    }
}
