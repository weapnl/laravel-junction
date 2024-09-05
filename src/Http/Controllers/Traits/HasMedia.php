<?php

namespace Weap\Junction\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Weap\Junction\Http\Utilities\MediaFile;
use Weap\Junction\Models\MediaTemporaryUpload;

trait HasMedia
{
    /**
     * @param Model $model
     * @param array<string, mixed> $validAttributes
     * @return array<Media>
     */
    public function attachMedia(Model $model, array $validAttributes): array
    {
        if (! class_exists(Media::class) || ! config('media-library.media_model')) {
            return [];
        }

        $mediaFiles = [];

        foreach ($validAttributes as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            if ($key !== '_media') {
                if ($model->$key instanceof Model) {
                    $this->attachMedia($model->$key, $value);
                }

                continue;
            }

            if (! $this->isValidMediaArray($value)) {
                continue;
            }

            foreach ($value as $collectionName => $uploadedFiles) {
                /** @var MediaFile $uploadedFile */
                foreach ($uploadedFiles as $uploadedFile) {
                    /** @var Media $media */
                    $media = config('media-library.media_model')::findOrFail($uploadedFile->mediaId);

                    abort_if($media->model_type !== MediaTemporaryUpload::class || Auth::id() !== $media->model->created_by_user_id, 404);

                    $oldMediaTemporaryUpload = $media->model;
                    $media->move($model, $collectionName);

                    if ($oldMediaTemporaryUpload->media->isEmpty()) {
                        $oldMediaTemporaryUpload->delete();
                    }
                }
            }
        }

        return $mediaFiles;
    }

    /**
     * @param array<string, mixed> $array
     * @return bool
     */
    private function isValidMediaArray(array $array): bool
    {
        foreach ($array as $key => $value) {
            if (! is_string($key) || array_values($value) !== $value || array_filter($value, static fn ($item) => $item instanceof MediaFile) !== $value) {
                return false;
            }
        }

        return true;
    }
}
