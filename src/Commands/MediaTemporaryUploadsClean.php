<?php

namespace Weap\Junction\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Weap\Junction\Models\MediaTemporaryUpload;

class MediaTemporaryUploadsClean extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:clean-media-temporary-uploads';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
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
