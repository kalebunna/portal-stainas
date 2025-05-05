<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProdiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Prodi::query();

        // Filter berdasarkan jenjang
        if ($request->has('jenjang')) {
            $query->where('jenjang', $request->jenjang);
        }

        // Cari berdasarkan nama
        if ($request->has('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        // Urutkan berdasarkan parameter
        $sortBy = $request->input('sort_by', 'nama');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginasi atau ambil semua
        if ($request->has('per_page')) {
            $perPage = $request->input('per_page', 10);
            $prodis = $query->paginate($perPage);
        } else {
            $prodis = $query->get();
        }

        return response()->json([
            'prodis' => $prodis
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
            'nama' => 'required|string|max:255',
            'jenjang' => 'required|string|max:50',
            'kode' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string',
            'visi' => 'nullable|string',
            'misi' => 'nullable|string',
            'akreditasi' => 'nullable|string|max:50',
            'gelar' => 'nullable|string|max:100',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'ketua_prodi' => 'nullable|string|max:255',
            'kompetensi' => 'nullable|string',
            'kurikulum' => 'nullable|string',
            'prospek_kerja' => 'nullable|string',
            'durasi_tahun' => 'nullable|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $prodi = new Prodi();
        $prodi->nama = $request->nama;
        $prodi->slug = Str::slug($request->nama);
        $prodi->jenjang = $request->jenjang;
        $prodi->kode = $request->kode;
        $prodi->deskripsi = $request->deskripsi;
        $prodi->visi = $request->visi;
        $prodi->misi = $request->misi;
        $prodi->akreditasi = $request->akreditasi;
        $prodi->gelar = $request->gelar;
        $prodi->ketua_prodi = $request->ketua_prodi;
        $prodi->kompetensi = $request->kompetensi;
        $prodi->kurikulum = $request->kurikulum;
        $prodi->prospek_kerja = $request->prospek_kerja;
        $prodi->durasi_tahun = $request->durasi_tahun;

        // Upload gambar jika ada
        if ($request->hasFile('gambar')) {
            $gambarPath = $request->file('gambar')->store('images/prodi', 'public');
            $prodi->gambar = $gambarPath;
        }

        // Upload icon jika ada
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('images/prodi/icons', 'public');
            $prodi->icon = $iconPath;
        }

        $prodi->save();

        return response()->json([
            'message' => 'Program studi created successfully',
            'prodi' => $prodi
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
        $prodi = Prodi::where('slug', $slug)->firstOrFail();

        return response()->json([
            'prodi' => $prodi
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
            'nama' => 'required|string|max:255',
            'jenjang' => 'required|string|max:50',
            'kode' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string',
            'visi' => 'nullable|string',
            'misi' => 'nullable|string',
            'akreditasi' => 'nullable|string|max:50',
            'gelar' => 'nullable|string|max:100',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'ketua_prodi' => 'nullable|string|max:255',
            'kompetensi' => 'nullable|string',
            'kurikulum' => 'nullable|string',
            'prospek_kerja' => 'nullable|string',
            'durasi_tahun' => 'nullable|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $prodi = Prodi::findOrFail($id);

        // Update nama dan cek jika perlu update slug
        if ($prodi->nama !== $request->nama) {
            $prodi->nama = $request->nama;
            $prodi->slug = Str::slug($request->nama);
        }

        $prodi->jenjang = $request->jenjang;
        $prodi->kode = $request->kode;
        $prodi->deskripsi = $request->deskripsi;
        $prodi->visi = $request->visi;
        $prodi->misi = $request->misi;
        $prodi->akreditasi = $request->akreditasi;
        $prodi->gelar = $request->gelar;
        $prodi->ketua_prodi = $request->ketua_prodi;
        $prodi->kompetensi = $request->kompetensi;
        $prodi->kurikulum = $request->kurikulum;
        $prodi->prospek_kerja = $request->prospek_kerja;
        $prodi->durasi_tahun = $request->durasi_tahun;

        // Upload gambar baru jika ada
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($prodi->gambar && Storage::disk('public')->exists($prodi->gambar)) {
                Storage::disk('public')->delete($prodi->gambar);
            }

            // Upload gambar baru
            $gambarPath = $request->file('gambar')->store('images/prodi', 'public');
            $prodi->gambar = $gambarPath;
        }

        // Upload icon baru jika ada
        if ($request->hasFile('icon')) {
            // Hapus icon lama jika ada
            if ($prodi->icon && Storage::disk('public')->exists($prodi->icon)) {
                Storage::disk('public')->delete($prodi->icon);
            }

            // Upload icon baru
            $iconPath = $request->file('icon')->store('images/prodi/icons', 'public');
            $prodi->icon = $iconPath;
        }

        $prodi->save();

        return response()->json([
            'message' => 'Program studi updated successfully',
            'prodi' => $prodi
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
        $prodi = Prodi::findOrFail($id);

        // Hapus gambar jika ada
        if ($prodi->gambar && Storage::disk('public')->exists($prodi->gambar)) {
            Storage::disk('public')->delete($prodi->gambar);
        }

        // Hapus icon jika ada
        if ($prodi->icon && Storage::disk('public')->exists($prodi->icon)) {
            Storage::disk('public')->delete($prodi->icon);
        }

        // Periksa apakah ada mahasiswa yang terkait dengan prodi ini
        if ($prodi->mahasiswas()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete program studi because it has related mahasiswa',
                'has_relations' => true
            ], 422);
        }

        $prodi->delete();

        return response()->json([
            'message' => 'Program studi deleted successfully'
        ]);
    }

    /**
     * Get program studi for dropdown.
     *
     * @return \Illuminate\Http\Response
     */
    public function getForDropdown()
    {
        $prodis = Prodi::select('id', 'nama', 'jenjang')->orderBy('nama', 'asc')->get();

        return response()->json([
            'prodis' => $prodis
        ]);
    }
}
