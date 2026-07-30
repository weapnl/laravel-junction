<?php

namespace Weap\Junction\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;
use Weap\Junction\Http\Utilities\MediaFile;
use Weap\Junction\Junction;

trait HasMedia
{
    /**
     * @param Model $model
     * @param array<string, mixed> $validAttributes
     * @return array<Media>
     *
     * @throws Throwable
     */
    public function attachMedia(Model $model, array $validAttributes): array
    {
        if (! $model instanceof \Spatie\MediaLibrary\HasMedia || ! is_a(config('media-library.media_model'), Media::class, true)) {
            return [];
        }

        $mediaTemporaryUploadModel = Junction::getMediaTemporaryUploadModel();

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

                    $oldMediaTemporaryUpload = $media->model;

                    abort_if(! $oldMediaTemporaryUpload instanceof $mediaTemporaryUploadModel || Auth::id() !== $oldMediaTemporaryUpload->created_by_user_id, 404);

                    $media = $this->beforeMediaUpload($media, $model, $collectionName);

                    $media = $media->move($model, $collectionName);

                    $mediaFiles[] = $media;

                    if ($oldMediaTemporaryUpload->media->isEmpty()) {
                        $oldMediaTemporaryUpload->delete();
                    }

                    $this->afterMediaUpload($media, $model);
                }
            }
        }

        return $mediaFiles;
    }

    /**
     * @param array<array-key, mixed> $array
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

    /**
     * @param Media $media
     * @param Model $model
     * @param string $collectionName
     * @return Media
     */
    public function beforeMediaUpload(Media $media, Model $model, string $collectionName): Media
    {
        return $media;
    }

    /**
     * @param Media $media
     * @param Model $model
     * @return void
     */
    public function afterMediaUpload(Media $media, Model $model): void
    {
    }
}
