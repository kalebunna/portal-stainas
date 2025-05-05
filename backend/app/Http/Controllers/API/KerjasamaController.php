<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Kerjasama;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class KerjasamaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Kerjasama::query();

        // Filter berdasarkan status aktif
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter berdasarkan jenis
        if ($request->has('jenis') && $request->jenis) {
            $query->where('jenis', $request->jenis);
        }

        // Filter berdasarkan tanggal (masih aktif)
        if ($request->has('active_only') && $request->active_only) {
            $query->where(function ($q) {
                $q->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', now()->toDateString());
            });
        }

        // Cari berdasarkan nama instansi
        if ($request->has('search') && $request->search) {
            $query->where('nama_instansi', 'like', '%' . $request->search . '%');
        }

        // Urutkan berdasarkan parameter
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginasi atau ambil semua
        if ($request->has('per_page')) {
            $perPage = $request->input('per_page', 10);
            $kerjasama = $query->paginate($perPage);
        } else {
            $kerjasama = $query->get();
        }

        return response()->json([
            'kerjasama' => $kerjasama
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
            'nama_instansi' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'jenis' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'manfaat' => 'nullable|string',
            'dokumen' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $kerjasama = new Kerjasama();
        $kerjasama->nama_instansi = $request->nama_instansi;
        $kerjasama->slug = Str::slug($request->nama_instansi) . '-' . time();
        $kerjasama->deskripsi = $request->deskripsi;
        $kerjasama->jenis = $request->jenis;
        $kerjasama->tanggal_mulai = $request->tanggal_mulai;
        $kerjasama->tanggal_selesai = $request->tanggal_selesai;
        $kerjasama->manfaat = $request->manfaat;
        $kerjasama->is_active = $request->is_active ?? true;

        // Upload logo jika ada
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('images/kerjasama', 'public');
            $kerjasama->logo = $logoPath;
        }

        // Upload dokumen jika ada
        if ($request->hasFile('dokumen')) {
            $dokumenPath = $request->file('dokumen')->store('documents/kerjasama', 'public');
            $kerjasama->dokumen = $dokumenPath;
        }

        $kerjasama->save();

        return response()->json([
            'message' => 'Kerjasama created successfully',
            'kerjasama' => $kerjasama
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
        $kerjasama = Kerjasama::where('slug', $slug)->firstOrFail();

        return response()->json([
            'kerjasama' => $kerjasama
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
        $validator = Validator::make($request->all(), [
            'nama_instansi' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'jenis' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'manfaat' => 'nullable|string',
            'dokumen' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $kerjasama = Kerjasama::findOrFail($id);

        // Update nama dan cek jika perlu update slug
        if ($kerjasama->nama_instansi !== $request->nama_instansi) {
            $kerjasama->nama_instansi = $request->nama_instansi;
            $kerjasama->slug = Str::slug($request->nama_instansi) . '-' . time();
        }

        $kerjasama->deskripsi = $request->deskripsi;
        $kerjasama->jenis = $request->jenis;
        $kerjasama->tanggal_mulai = $request->tanggal_mulai;
        $kerjasama->tanggal_selesai = $request->tanggal_selesai;
        $kerjasama->manfaat = $request->manfaat;
        $kerjasama->is_active = $request->is_active ?? $kerjasama->is_active;

        // Upload logo baru jika ada
        if ($request->hasFile('logo')) {
            // Hapus logo lama jika ada
            if ($kerjasama->logo && Storage::disk('public')->exists($kerjasama->logo)) {
                Storage::disk('public')->delete($kerjasama->logo);
            }

            // Upload logo baru
            $logoPath = $request->file('logo')->store('images/kerjasama', 'public');
            $kerjasama->logo = $logoPath;
        }

        // Upload dokumen baru jika ada
        if ($request->hasFile('dokumen')) {
            // Hapus dokumen lama jika ada
            if ($kerjasama->dokumen && Storage::disk('public')->exists($kerjasama->dokumen)) {
                Storage::disk('public')->delete($kerjasama->dokumen);
            }

            // Upload dokumen baru
            $dokumenPath = $request->file('dokumen')->store('documents/kerjasama', 'public');
            $kerjasama->dokumen = $dokumenPath;
        }

        $kerjasama->save();

        return response()->json([
            'message' => 'Kerjasama updated successfully',
            'kerjasama' => $kerjasama
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
        $kerjasama = Kerjasama::findOrFail($id);

        // Hapus logo jika ada
        if ($kerjasama->logo && Storage::disk('public')->exists($kerjasama->logo)) {
            Storage::disk('public')->delete($kerjasama->logo);
        }

        // Hapus dokumen jika ada
        if ($kerjasama->dokumen && Storage::disk('public')->exists($kerjasama->dokumen)) {
            Storage::disk('public')->delete($kerjasama->dokumen);
        }

        $kerjasama->delete();

        return response()->json([
            'message' => 'Kerjasama deleted successfully'
        ]);
    }

    /**
     * Toggle active status of the kerjasama.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleActive($id)
    {
        $kerjasama = Kerjasama::findOrFail($id);
        $kerjasama->is_active = !$kerjasama->is_active;
        $kerjasama->save();

        return response()->json([
            'message' => 'Kerjasama status toggled successfully',
            'is_active' => $kerjasama->is_active
        ]);
    }

    /**
     * Download dokumen kerjasama.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function downloadDokumen($id)
    {
        $kerjasama = Kerjasama::findOrFail($id);

        if (!$kerjasama->dokumen) {
            return response()->json(['error' => 'Dokumen tidak tersedia'], 404);
        }

        if (!Storage::disk('public')->exists($kerjasama->dokumen)) {
            return response()->json(['error' => 'File tidak ditemukan'], 404);
        }

        $path = Storage::disk('public')->path($kerjasama->dokumen);
        $filename = basename($path);

        return response()->download($path, $filename);
    }

    /**
     * Get kerjasama types for filter/dropdown.
     *
     * @return \Illuminate\Http\Response
     */
    public function getJenis()
    {
        $jenis = Kerjasama::distinct()->pluck('jenis')->filter()->values();

        return response()->json([
            'jenis' => $jenis
        ]);
    }
}
