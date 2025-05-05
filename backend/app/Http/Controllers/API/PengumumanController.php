<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PengumumanController extends Controller
{
    public function index(Request $request)
    {
        $query = Pengumuman::with('user');

        // Filter berdasarkan status publikasi
        if ($request->has('is_published')) {
            $query->where('is_published', $request->is_published);
        }

        // Filter berdasarkan tipe
        if ($request->has('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        // Filter pengumuman yang masih aktif
        if ($request->has('active_only') && $request->active_only) {
            $query->where(function ($q) {
                $q->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', now()->toDateString());
            })
                ->where('tanggal_mulai', '<=', now()->toDateString());
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
        $pengumuman = $query->paginate($perPage);

        return response()->json([
            'pengumuman' => $pengumuman
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'konten' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_published' => 'boolean',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'tipe' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pengumuman = new Pengumuman();
        $pengumuman->judul = $request->judul;
        $pengumuman->slug = Str::slug($request->judul) . '-' . time();
        $pengumuman->konten = $request->konten;
        $pengumuman->user_id = Auth::id();
        $pengumuman->is_published = $request->is_published ?? false;
        $pengumuman->tanggal_mulai = $request->tanggal_mulai ?? now()->toDateString();
        $pengumuman->tanggal_selesai = $request->tanggal_selesai;
        $pengumuman->tipe = $request->tipe;

        // Upload gambar jika ada
        if ($request->hasFile('gambar')) {
            $gambarPath = $request->file('gambar')->store('images/pengumuman', 'public');
            $pengumuman->gambar = $gambarPath;
        }

        $pengumuman->save();

        return response()->json([
            'message' => 'Pengumuman created successfully',
            'pengumuman' => $pengumuman
        ], 201);
    }

    public function show($slug)
    {
        $pengumuman = Pengumuman::where('slug', $slug)->with('user')->firstOrFail();

        return response()->json([
            'pengumuman' => $pengumuman
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'konten' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_published' => 'boolean',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'tipe' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pengumuman = Pengumuman::findOrFail($id);

        // Update judul dan cek jika perlu update slug
        if ($pengumuman->judul !== $request->judul) {
            $pengumuman->judul = $request->judul;
            $pengumuman->slug = Str::slug($request->judul) . '-' . time();
        }

        $pengumuman->konten = $request->konten;
        $pengumuman->is_published = $request->is_published ?? $pengumuman->is_published;
        $pengumuman->tanggal_mulai = $request->tanggal_mulai ?? $pengumuman->tanggal_mulai;
        $pengumuman->tanggal_selesai = $request->tanggal_selesai;
        $pengumuman->tipe = $request->tipe;

        // Upload gambar baru jika ada
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($pengumuman->gambar && Storage::disk('public')->exists($pengumuman->gambar)) {
                Storage::disk('public')->delete($pengumuman->gambar);
            }

            // Upload gambar baru
            $gambarPath = $request->file('gambar')->store('images/pengumuman', 'public');
            $pengumuman->gambar = $gambarPath;
        }

        $pengumuman->save();

        return response()->json([
            'message' => 'Pengumuman updated successfully',
            'pengumuman' => $pengumuman
        ]);
    }

    public function destroy($id)
    {
        $pengumuman = Pengumuman::findOrFail($id);

        // Hapus gambar jika ada
        if ($pengumuman->gambar && Storage::disk('public')->exists($pengumuman->gambar)) {
            Storage::disk('public')->delete($pengumuman->gambar);
        }

        $pengumuman->delete();

        return response()->json([
            'message' => 'Pengumuman deleted successfully'
        ]);
    }

    public function getLatest($limit = 5)
    {
        $pengumuman = Pengumuman::where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', now()->toDateString());
            })
            ->where('tanggal_mulai', '<=', now()->toDateString())
            ->orderBy('tanggal_mulai', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'pengumuman' => $pengumuman
        ]);
    }
}
