<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class MediaController extends Controller
{
    /**
     * Display a listing of media files.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Media::query();

        // Filter berdasarkan tipe
        if ($request->has('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        // Cari berdasarkan nama
        if ($request->has('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        // Urutkan berdasarkan parameter
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginasi
        $perPage = $request->input('per_page', 20);
        $media = $query->paginate($perPage);

        return response()->json([
            'media' => $media
        ]);
    }

    /**
     * Upload a new media file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // max 10MB
            'nama' => 'nullable|string|max:255',
            'tipe' => 'required|string|in:image,document,video',
            'mediable_type' => 'nullable|string',
            'mediable_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');
        $fileOriginalName = $file->getClientOriginalName();
        $fileExtension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();
        $fileMimeType = $file->getMimeType();

        // Generate file name
        $fileName = $request->input('nama') ?? pathinfo($fileOriginalName, PATHINFO_FILENAME);
        $fileName = $this->sanitizeFileName($fileName) . '_' . time() . '.' . $fileExtension;

        // Determine storage path based on file type
        $tipe = $request->input('tipe');
        $directory = 'uploads';

        switch ($tipe) {
            case 'image':
                $directory = 'images';
                break;
            case 'document':
                $directory = 'documents';
                break;
            case 'video':
                $directory = 'videos';
                break;
        }

        // Store file
        $path = $file->store($directory, 'public');

        // Create thumbnail for images
        $thumbnailPath = null;
        if ($tipe === 'image' && strpos($fileMimeType, 'image/') === 0) {
            $thumbnailPath = $this->createThumbnail($file, $directory);
        }

        // Create media record
        $media = new Media();
        $media->nama = $request->input('nama') ?? pathinfo($fileOriginalName, PATHINFO_FILENAME);
        $media->file = $path;
        $media->tipe = $tipe;
        $media->mime = $fileMimeType;
        $media->ukuran = $fileSize;

        // Set mediable if provided
        if ($request->filled('mediable_type') && $request->filled('mediable_id')) {
            $media->mediable_type = $request->input('mediable_type');
            $media->mediable_id = $request->input('mediable_id');
        }

        $media->save();

        // Add thumbnail path to response if created
        $responseData = [
            'message' => 'File uploaded successfully',
            'media' => $media,
            'url' => Storage::disk('public')->url($path)
        ];

        if ($thumbnailPath) {
            $responseData['thumbnail_url'] = Storage::disk('public')->url($thumbnailPath);
        }

        return response()->json($responseData, 201);
    }

    /**
     * Display the specified media.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $media = Media::findOrFail($id);

        return response()->json([
            'media' => $media,
            'url' => Storage::disk('public')->url($media->file)
        ]);
    }

    /**
     * Update the specified media in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $media = Media::findOrFail($id);
        $media->nama = $request->nama;
        $media->save();

        return response()->json([
            'message' => 'Media updated successfully',
            'media' => $media
        ]);
    }

    /**
     * Remove the specified media from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $media = Media::findOrFail($id);

        // Delete file from storage
        if (Storage::disk('public')->exists($media->file)) {
            Storage::disk('public')->delete($media->file);
        }

        // Delete thumbnail if it's an image
        if ($media->tipe === 'image') {
            $thumbnailPath = $this->getThumbnailPath($media->file);
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
        }

        $media->delete();

        return response()->json([
            'message' => 'Media deleted successfully'
        ]);
    }

    /**
     * Get media files by mediable type and id.
     *
     * @param  string  $type
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getByMediable($type, $id)
    {
        $media = Media::where('mediable_type', $type)
            ->where('mediable_id', $id)
            ->get();

        // Add URLs
        $media->each(function ($item) {
            $item->url = Storage::disk('public')->url($item->file);

            if ($item->tipe === 'image') {
                $thumbnailPath = $this->getThumbnailPath($item->file);
                if (Storage::disk('public')->exists($thumbnailPath)) {
                    $item->thumbnail_url = Storage::disk('public')->url($thumbnailPath);
                }
            }
        });

        return response()->json([
            'media' => $media
        ]);
    }

    /**
     * Create a thumbnail for image file.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $directory
     * @return string|null
     */
    private function createThumbnail($file, $directory)
    {
        try {
            $thumbnailDirectory = $directory . '/thumbnails';

            // Ensure thumbnail directory exists
            if (!Storage::disk('public')->exists($thumbnailDirectory)) {
                Storage::disk('public')->makeDirectory($thumbnailDirectory);
            }

            $fileExtension = $file->getClientOriginalExtension();
            $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $thumbnailName = $this->sanitizeFileName($fileName) . '_' . time() . '_thumb.' . $fileExtension;
            $thumbnailPath = $thumbnailDirectory . '/' . $thumbnailName;

            // Create thumbnail with Intervention Image
            $img = Image::make($file);
            $img->fit(300, 300, function ($constraint) {
                $constraint->upsize();
            });

            $img->save(storage_path('app/public/' . $thumbnailPath));

            return $thumbnailPath;
        } catch (\Exception $e) {
            // Log error but continue
            \Log::error('Failed to create thumbnail: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get thumbnail path from original file path.
     *
     * @param  string  $filePath
     * @return string
     */
    private function getThumbnailPath($filePath)
    {
        $pathInfo = pathinfo($filePath);
        return $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
    }

    /**
     * Sanitize file name.
     *
     * @param  string  $fileName
     * @return string
     */
    private function sanitizeFileName($fileName)
    {
        // Remove any character that is not a letter, digit, space, underscore or dash
        $fileName = preg_replace('/[^\w\s-]/u', '', $fileName);
        // Replace spaces with underscores
        $fileName = preg_replace('/\s+/u', '_', $fileName);
        // Remove multiple underscores
        $fileName = preg_replace('/_+/u', '_', $fileName);
        // Trim underscores from start and end
        $fileName = trim($fileName, '_');

        return $fileName;
    }
}
