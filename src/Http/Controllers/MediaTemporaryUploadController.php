<?php

namespace Weap\Junction\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Weap\Junction\Models\MediaTemporaryUpload;

class MediaTemporaryUploadController extends Controller
{
    /**
     * The class name of the model for which the controller should implement CRUD actions.
     *
     * @var string
     */
    public $model = MediaTemporaryUpload::class;

    /**
     * @return JsonResponse
     *
     */
    public function upload(): JsonResponse
    {
        abort_if(! class_exists(\Spatie\MediaLibrary\MediaCollections\Models\Media::class), 404);

        $validated = request()->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file'],
        ]);

        $mediaTemporaryUpload = new MediaTemporaryUpload();
        $mediaTemporaryUpload->createdBy()->associate(Auth::user());
        $mediaTemporaryUpload->save();

        $mediaIds = [];
        foreach ($validated['files'] as $file) {
            $mediaIds[] = $mediaTemporaryUpload->addMedia($file)->toMediaCollection()->id;
        }

        return response()->json($mediaIds);
    }
}
