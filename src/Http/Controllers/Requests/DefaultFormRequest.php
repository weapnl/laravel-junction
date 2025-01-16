<?php

namespace Weap\Junction\Http\Controllers\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Weap\Junction\Http\Utilities\MediaFile;
use Weap\Junction\Models\MediaTemporaryUpload;

class DefaultFormRequest extends FormRequest
{
    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if (class_exists(Media::class) && config('media-library.media_model')) {
            $this->merge($this->prepareMedia($this->all()));
        }
    }

    /**
     * @param Validator $validator
     * @return void
     */
    protected function failedValidation(Validator $validator)
    {
        if (config('media-library.disk_name') !== 'local') {
            $this->clearTempMediaFiles($this->all());
        }

        parent::failedValidation($validator);
    }

    /**
     * @return void
     */
    protected function passedValidation()
    {
        if (config('media-library.disk_name') !== 'local') {
            $this->clearTempMediaFiles($this->all());
        }

        parent::passedValidation();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepareMedia(array $data): array
    {
        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            if ($key !== '_media' || ! $this->isValidMediaArray($value)) {
                $data[$key] = $this->prepareMedia($value);

                continue;
            }

            $mediaArray = [];

            foreach ($value as $collectionName => $mediaItems) {
                foreach ($mediaItems as $mediaId) {
                    $media = config('media-library.media_model')::find($mediaId);

                    if (! $media || $media->model_type !== MediaTemporaryUpload::class) {
                        $mediaArray[$collectionName][] = $mediaId;

                        continue;
                    }

                    abort_if(Auth::id() !== $media->model->created_by_user_id, 404);

                    if (config('media-library.disk_name') === 'local') {
                        $mediaArray[$collectionName][] = new MediaFile($media->getPath(), $mediaId);
                    } else {
                        $tempFilePath = tempnam(storage_path(), 'junction-temp-media-');
                        file_put_contents($tempFilePath, stream_get_contents($media->stream()));

                        $mediaArray[$collectionName][] = new MediaFile($tempFilePath, $mediaId);
                    }
                }
            }

            $data[$key] = $mediaArray;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    private function clearTempMediaFiles(array $data): void
    {
        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            if ($key !== '_media' || ! $this->isValidMediaFileArray($value)) {
                $this->clearTempMediaFiles($value);

                continue;
            }

            foreach ($value as $mediaFiles) {
                /** @var MediaFile $mediaFile */
                foreach ($mediaFiles as $mediaFile) {
                    if (file_exists($mediaFile->getRealPath())) {
                        unlink($mediaFile->getRealPath());
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
        $hasNonEmptyValue = false;

        foreach ($array as $key => $value) {
            if (! is_string($key)) {
                return false;
            }

            if (! empty($value)) {
                $hasNonEmptyValue = true;

                if (array_values($value) !== $value || array_filter($value, 'is_int') !== $value) {
                    return false;
                }
            }
        }

        return $hasNonEmptyValue;
    }

    /**
     * @param array<string, array<mixed, mixed>> $array
     * @return bool
     */
    private function isValidMediaFileArray(array $array): bool
    {
        foreach ($array as $value) {
            if (empty($value)) {
                return false;
            }

            foreach ($value as $item) {
                if (! ($item instanceof MediaFile)) {
                    return false;
                }
            }
        }

        return true;
    }
}
