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
     * @return void
     */
    public function attachMedia(Model $model, array $validAttributes): void
    {
        if (! class_exists(Media::class) || ! config('media-library.media_model')) {
            return;
        }

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
                    $media = config('media-library.media_model')::findOrFail($uploadedFile->mediaId);

                    abort_if($media->model_type !== MediaTemporaryUpload::class || Auth::user()->id !== $media->model->created_by_user_id, 404);

                    $oldMediaTemporaryUpload = $media->model;

                    $media->model()->associate($model);
                    $media->collection_name = $collectionName;
                    $media->save();

                    // This is to respect the `singleFile` prop on the media model.
                    if ($collectionSizeLimit = optional($model->getMediaCollection($media->collection_name))->collectionSizeLimit) {
                        $collectionMedia = $model->getMedia($media->collection_name);

                        if ($collectionMedia->count() > $collectionSizeLimit) {
                            $model->clearMediaCollectionExcept($media->collection_name, $media);
                        }
                    }

                    if ($oldMediaTemporaryUpload->media->isEmpty()) {
                        $oldMediaTemporaryUpload->delete();
                    }
                }
            }
        }
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
