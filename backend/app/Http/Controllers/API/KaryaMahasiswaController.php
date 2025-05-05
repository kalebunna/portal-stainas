<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KaryaMahasiswa;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class KaryaMahasiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = KaryaMahasiswa::with(['mahasiswa', 'mahasiswa.prodi', 'user', 'approvedBy']);

        // Filter berdasarkan status publikasi
        if ($request->has('is_published')) {
            $query->where('is_published', $request->is_published);
        }

        // Filter berdasarkan apakah sudah disetujui
        if ($request->has('is_approved')) {
            if ($request->is_approved) {
                $query->whereNotNull('approved_at');
            } else {
                $query->whereNull('approved_at');
            }
        }

        // Filter berdasarkan jenis karya
        if ($request->has('jenis') && $request->jenis) {
            $query->where('jenis', $request->jenis);
        }

        // Filter berdasarkan mahasiswa
        if ($request->has('mahasiswa_id') && $request->mahasiswa_id) {
            $query->where('mahasiswa_id', $request->mahasiswa_id);
        }

        // Filter berdasarkan prodi (melalui relasi mahasiswa)
        if ($request->has('prodi_id') && $request->prodi_id) {
            $query->whereHas('mahasiswa', function ($q) use ($request) {
                $q->where('prodi_id', $request->prodi_id);
            });
        }

        // Cari berdasarkan judul
        if ($request->has('search') && $request->search) {
            $query->where('judul', 'like', '%' . $request->search . '%');
        }

        // Filter berdasarkan user yang login (untuk mahasiswa)
        if ($request->has('my_karya') && $request->my_karya && Auth::check()) {
            $user = Auth::user();
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if ($mahasiswa) {
                $query->where('mahasiswa_id', $mahasiswa->id);
            } else {
                // Jika user tidak terkait dengan mahasiswa, return empty result
                return response()->json([
                    'karya_mahasiswa' => []
                ]);
            }
        }

        // Urutkan berdasarkan parameter
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginasi
        $perPage = $request->input('per_page', 10);
        $karyaMahasiswa = $query->paginate($perPage);

        return response()->json([
            'karya_mahasiswa' => $karyaMahasiswa
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'mahasiswa_id' => 'required|exists:mahasiswas,id',
            'jenis' => 'required|string|max:100',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'file' => 'nullable|file|max:20480', // Max 20MB
            'url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verifikasi user - jika bukan admin, harus mahasiswa sendiri yang upload
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');

        if (!$isAdmin) {
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if (!$mahasiswa || $mahasiswa->id != $request->mahasiswa_id) {
                return response()->json([
                    'error' => 'Anda hanya dapat mengunggah karya untuk diri sendiri'
                ], 403);
            }
        }

        $karyaMahasiswa = new KaryaMahasiswa();
        $karyaMahasiswa->judul = $request->judul;
        $karyaMahasiswa->slug = Str::slug($request->judul) . '-' . time();
        $karyaMahasiswa->deskripsi = $request->deskripsi;
        $karyaMahasiswa->mahasiswa_id = $request->mahasiswa_id;
        $karyaMahasiswa->user_id = $user->id;
        $karyaMahasiswa->jenis = $request->jenis;
        $karyaMahasiswa->url = $request->url;

        // Status publikasi - jika admin, bisa langsung dipublish dan diapprove
        if ($isAdmin) {
            $karyaMahasiswa->is_published = $request->is_published ?? true;

            if ($karyaMahasiswa->is_published) {
                $karyaMahasiswa->approved_by = $user->id;
                $karyaMahasiswa->approved_at = Carbon::now();
            }
        } else {
            // Untuk mahasiswa, karya tidak langsung dipublish dan perlu approval
            $karyaMahasiswa->is_published = false;
        }

        // Upload thumbnail jika ada
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('images/karya_mahasiswa/thumbnails', 'public');
            $karyaMahasiswa->thumbnail = $thumbnailPath;
        }

        // Upload file jika ada
        if ($request->hasFile('file')) {
            $fileName = Str::slug($request->judul) . '-' . time() . '.' . $request->file('file')->getClientOriginalExtension();
            $filePath = $request->file('file')->storeAs('files/karya_mahasiswa', $fileName, 'public');
            $karyaMahasiswa->file = $filePath;
        }

        $karyaMahasiswa->save();

        return response()->json([
            'message' => 'Karya mahasiswa created successfully',
            'karya_mahasiswa' => $karyaMahasiswa
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $karyaMahasiswa = KaryaMahasiswa::with(['mahasiswa', 'mahasiswa.prodi', 'user', 'approvedBy'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'karya_mahasiswa' => $karyaMahasiswa
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $karyaMahasiswa = KaryaMahasiswa::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'jenis' => 'required|string|max:100',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'file' => 'nullable|file|max:20480', // Max 20MB
            'url' => 'nullable|url|max:255',
            'is_published' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verifikasi user - jika bukan admin, harus pemilik karya
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');

        if (!$isAdmin) {
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if (!$mahasiswa || $mahasiswa->id != $karyaMahasiswa->mahasiswa_id) {
                return response()->json([
                    'error' => 'Anda hanya dapat mengubah karya milik sendiri'
                ], 403);
            }

            // Jika karya sudah diapprove, mahasiswa tidak bisa mengubah status publikasi
            if ($request->has('is_published') && $karyaMahasiswa->approved_at) {
                return response()->json([
                    'error' => 'Anda tidak dapat mengubah status publikasi karya yang sudah disetujui'
                ], 403);
            }
        }

        // Update judul dan slug jika judul berubah
        if ($karyaMahasiswa->judul !== $request->judul) {
            $karyaMahasiswa->judul = $request->judul;
            $karyaMahasiswa->slug = Str::slug($request->judul) . '-' . time();
        }

        $karyaMahasiswa->deskripsi = $request->deskripsi;
        $karyaMahasiswa->jenis = $request->jenis;
        $karyaMahasiswa->url = $request->url;

        // Update status publikasi jika diizinkan
        if ($isAdmin && $request->has('is_published')) {
            $karyaMahasiswa->is_published = $request->is_published;

            // Jika dipublish dan belum diapprove, approve sekarang
            if ($request->is_published && !$karyaMahasiswa->approved_at) {
                $karyaMahasiswa->approved_by = $user->id;
                $karyaMahasiswa->approved_at = Carbon::now();
            }
        }

        // Upload thumbnail baru jika ada
        if ($request->hasFile('thumbnail')) {
            // Hapus thumbnail lama jika ada
            if ($karyaMahasiswa->thumbnail && Storage::disk('public')->exists($karyaMahasiswa->thumbnail)) {
                Storage::disk('public')->delete($karyaMahasiswa->thumbnail);
            }

            // Upload thumbnail baru
            $thumbnailPath = $request->file('thumbnail')->store('images/karya_mahasiswa/thumbnails', 'public');
            $karyaMahasiswa->thumbnail = $thumbnailPath;
        }

        // Upload file baru jika ada
        if ($request->hasFile('file')) {
            // Hapus file lama jika ada
            if ($karyaMahasiswa->file && Storage::disk('public')->exists($karyaMahasiswa->file)) {
                Storage::disk('public')->delete($karyaMahasiswa->file);
            }

            // Upload file baru
            $fileName = Str::slug($request->judul) . '-' . time() . '.' . $request->file('file')->getClientOriginalExtension();
            $filePath = $request->file('file')->storeAs('files/karya_mahasiswa', $fileName, 'public');
            $karyaMahasiswa->file = $filePath;
        }

        $karyaMahasiswa->save();

        return response()->json([
            'message' => 'Karya mahasiswa updated successfully',
            'karya_mahasiswa' => $karyaMahasiswa
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $karyaMahasiswa = KaryaMahasiswa::findOrFail($id);

        // Verifikasi user - jika bukan admin, harus pemilik karya
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');

        if (!$isAdmin) {
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if (!$mahasiswa || $mahasiswa->id != $karyaMahasiswa->mahasiswa_id) {
                return response()->json([
                    'error' => 'Anda hanya dapat menghapus karya milik sendiri'
                ], 403);
            }

            // Jika karya sudah diapprove, mahasiswa tidak bisa menghapusnya
            if ($karyaMahasiswa->approved_at) {
                return response()->json([
                    'error' => 'Anda tidak dapat menghapus karya yang sudah disetujui. Hubungi admin untuk bantuan.'
                ], 403);
            }
        }

        // Hapus thumbnail jika ada
        if ($karyaMahasiswa->thumbnail && Storage::disk('public')->exists($karyaMahasiswa->thumbnail)) {
            Storage::disk('public')->delete($karyaMahasiswa->thumbnail);
        }

        // Hapus file jika ada
        if ($karyaMahasiswa->file && Storage::disk('public')->exists($karyaMahasiswa->file)) {
            Storage::disk('public')->delete($karyaMahasiswa->file);
        }

        $karyaMahasiswa->delete();

        return response()->json([
            'message' => 'Karya mahasiswa deleted successfully'
        ]);
    }

    /**
     * Approve a karya mahasiswa.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request, $id)
    {
        // Hanya admin yang dapat menyetujui karya
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'error' => 'Anda tidak memiliki izin untuk menyetujui karya'
            ], 403);
        }

        $karyaMahasiswa = KaryaMahasiswa::findOrFail($id);

        // Check if already approved
        if ($karyaMahasiswa->approved_at) {
            return response()->json([
                'message' => 'Karya mahasiswa sudah disetujui sebelumnya',
                'karya_mahasiswa' => $karyaMahasiswa
            ]);
        }

        $karyaMahasiswa->approved_by = Auth::id();
        $karyaMahasiswa->approved_at = Carbon::now();
        $karyaMahasiswa->is_published = true;
        $karyaMahasiswa->save();

        return response()->json([
            'message' => 'Karya mahasiswa approved successfully',
            'karya_mahasiswa' => $karyaMahasiswa
        ]);
    }

    /**
     * Reject a karya mahasiswa.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reject(Request $request, $id)
    {
        // Hanya admin yang dapat menolak karya
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'error' => 'Anda tidak memiliki izin untuk menolak karya'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'alasan_penolakan' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $karyaMahasiswa = KaryaMahasiswa::findOrFail($id);

        // Reset approval status
        $karyaMahasiswa->approved_by = null;
        $karyaMahasiswa->approved_at = null;
        $karyaMahasiswa->is_published = false;
        $karyaMahasiswa->alasan_penolakan = $request->alasan_penolakan;
        $karyaMahasiswa->save();

        return response()->json([
            'message' => 'Karya mahasiswa rejected successfully',
            'karya_mahasiswa' => $karyaMahasiswa
        ]);
    }

    /**
     * Download file karya mahasiswa.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function downloadFile($id)
    {
        $karyaMahasiswa = KaryaMahasiswa::findOrFail($id);

        if (!$karyaMahasiswa->file) {
            return response()->json(['error' => 'File tidak tersedia'], 404);
        }

        if (!Storage::disk('public')->exists($karyaMahasiswa->file)) {
            return response()->json(['error' => 'File tidak ditemukan'], 404);
        }

        $path = Storage::disk('public')->path($karyaMahasiswa->file);
        $fileName = $karyaMahasiswa->judul . '.' . pathinfo($path, PATHINFO_EXTENSION);

        return response()->download($path, $fileName);
    }

    /**
     * Get jenis karya for filter/dropdown.
     *
     * @return \Illuminate\Http\Response
     */
    public function getJenis()
    {
        $jenis = KaryaMahasiswa::distinct()->pluck('jenis')->filter()->values();

        return response()->json([
            'jenis' => $jenis
        ]);
    }

    /**
     * Get approval statistics for admin dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function getApprovalStats()
    {
        // Hanya admin yang dapat melihat statistik
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'error' => 'Anda tidak memiliki izin untuk melihat statistik ini'
            ], 403);
        }

        $totalKarya = KaryaMahasiswa::count();
        $approvedKarya = KaryaMahasiswa::whereNotNull('approved_at')->count();
        $pendingKarya = KaryaMahasiswa::whereNull('approved_at')->count();
        $publishedKarya = KaryaMahasiswa::where('is_published', true)->count();

        // Karya per jenis
        $karyaPerJenis = KaryaMahasiswa::selectRaw('jenis, count(*) as total')
            ->groupBy('jenis')
            ->get();

        // Karya per prodi
        $karyaPerProdi = KaryaMahasiswa::with('mahasiswa.prodi')
            ->get()
            ->groupBy('mahasiswa.prodi.nama')
            ->map(function ($items, $key) {
                return ['prodi' => $key, 'total' => $items->count()];
            })
            ->values();

        return response()->json([
            'total' => $totalKarya,
            'approved' => $approvedKarya,
            'pending' => $pendingKarya,
            'published' => $publishedKarya,
            'per_jenis' => $karyaPerJenis,
            'per_prodi' => $karyaPerProdi
        ]);
    }
}
