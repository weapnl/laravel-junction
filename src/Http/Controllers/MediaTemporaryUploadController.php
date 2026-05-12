<?php

namespace Weap\Junction\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Weap\Junction\Models\MediaTemporaryUpload;

class MediaTemporaryUploadController extends Controller
{
    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct(config('junction.route.media.media_temporary_upload_model', MediaTemporaryUpload::class));
    }

    /**
     * @return JsonResponse
     */
    public function upload(): JsonResponse
    {
        abort_if(! class_exists(Media::class), 404);

        $validated = request()->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file'],
        ]);

        $mediaTemporaryUpload = new $this->model();
        $mediaTemporaryUpload->createdBy()->associate(Auth::user());
        $mediaTemporaryUpload->save();

        $mediaIds = [];
        foreach ($validated['files'] as $file) {
            $mediaIds[] = $mediaTemporaryUpload->addMedia($file)->toMediaCollection()->id;
        }

        return response()->json($mediaIds);
    }
}
