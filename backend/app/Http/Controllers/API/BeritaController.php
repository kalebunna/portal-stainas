<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BeritaController extends Controller
{
    public function index(Request $request)
    {
        $query = Berita::with('user');

        // Filter berdasarkan status publikasi
        if ($request->has('is_published')) {
            $query->where('is_published', $request->is_published);
        }

        // Cari berdasarkan judul
        if ($request->has('search')) {
            $query->where('judul', 'like', '%' . $request->search . '%');
        }

        // Urutkan berdasarkan parameter
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginasi
        $perPage = $request->input('per_page', 10);
        $berita = $query->paginate($perPage);

        return response()->json([
            'berita' => $berita
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'konten' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $berita = new Berita();
        $berita->judul = $request->judul;
        $berita->slug = Str::slug($request->judul) . '-' . time();
        $berita->konten = $request->konten;
        $berita->user_id = Auth::id();
        $berita->is_published = $request->is_published ?? false;

        if ($berita->is_published) {
            $berita->published_at = Carbon::now();
        }

        // Upload gambar jika ada
        if ($request->hasFile('gambar')) {
            $gambarPath = $request->file('gambar')->store('images/berita', 'public');
            $berita->gambar = $gambarPath;

            // Buat thumbnail
            $this->createThumbnail($request->file('gambar'), $berita);
        }

        $berita->save();

        return response()->json([
            'message' => 'Berita created successfully',
            'berita' => $berita
        ], 201);
    }

    public function show($slug)
    {
        $berita = Berita::where('slug', $slug)->with('user')->firstOrFail();

        // Increment view count
        $berita->increment('views');

        return response()->json([
            'berita' => $berita
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'konten' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $berita = Berita::findOrFail($id);

        // Update judul dan cek jika perlu update slug
        if ($berita->judul !== $request->judul) {
            $berita->judul = $request->judul;
            $berita->slug = Str::slug($request->judul) . '-' . time();
        }

        $berita->konten = $request->konten;

        // Update status publikasi
        if (isset($request->is_published) && $berita->is_published != $request->is_published) {
            $berita->is_published = $request->is_published;

            if ($request->is_published) {
                $berita->published_at = Carbon::now();
            } else {
                $berita->published_at = null;
            }
        }

        // Upload gambar baru jika ada
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($berita->gambar && Storage::disk('public')->exists($berita->gambar)) {
                Storage::disk('public')->delete($berita->gambar);
            }

            // Hapus thumbnail lama jika ada
            if ($berita->thumbnail && Storage::disk('public')->exists($berita->thumbnail)) {
                Storage::disk('public')->delete($berita->thumbnail);
            }

            // Upload gambar baru
            $gambarPath = $request->file('gambar')->store('images/berita', 'public');
            $berita->gambar = $gambarPath;

            // Buat thumbnail baru
            $this->createThumbnail($request->file('gambar'), $berita);
        }

        $berita->save();

        return response()->json([
            'message' => 'Berita updated successfully',
            'berita' => $berita
        ]);
    }

    public function destroy($id)
    {
        $berita = Berita::findOrFail($id);

        // Hapus gambar dan thumbnail jika ada
        if ($berita->gambar && Storage::disk('public')->exists($berita->gambar)) {
            Storage::disk('public')->delete($berita->gambar);
        }

        if ($berita->thumbnail && Storage::disk('public')->exists($berita->thumbnail)) {
            Storage::disk('public')->delete($berita->thumbnail);
        }

        $berita->delete();

        return response()->json([
            'message' => 'Berita deleted successfully'
        ]);
    }

    public function getLatest($limit = 5)
    {
        $berita = Berita::where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'berita' => $berita
        ]);
    }

    // Helper untuk membuat thumbnail
    private function createThumbnail($image, &$berita)
    {
        $thumbnailPath = 'images/berita/thumbnails';

        // Buat direktori jika belum ada
        if (!Storage::disk('public')->exists($thumbnailPath)) {
            Storage::disk('public')->makeDirectory($thumbnailPath);
        }

        // Buat thumbnail dengan intervention/image
        $img = \Image::make($image);
        $img->fit(300, 200);

        $thumbnailFilename = 'thumb_' . time() . '.' . $image->getClientOriginalExtension();
        $img->save(storage_path('app/public/' . $thumbnailPath . '/' . $thumbnailFilename));

        $berita->thumbnail = $thumbnailPath . '/' . $thumbnailFilename;
    }
}
