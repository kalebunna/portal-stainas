<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Models\Prodi;
use App\Imports\MahasiswaImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class MahasiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Mahasiswa::with('prodi');

        // Filter berdasarkan prodi
        if ($request->has('prodi_id') && $request->prodi_id) {
            $query->where('prodi_id', $request->prodi_id);
        }

        // Filter berdasarkan status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan angkatan
        if ($request->has('angkatan') && $request->angkatan) {
            $query->where('angkatan', $request->angkatan);
        }

        // Cari berdasarkan nama, nim, atau email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('nim', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Urutkan berdasarkan parameter
        $sortBy = $request->input('sort_by', 'nama');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginasi
        $perPage = $request->input('per_page', 15);
        $mahasiswas = $query->paginate($perPage);

        return response()->json([
            'mahasiswas' => $mahasiswas
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
            'nim' => 'required|string|max:50|unique:mahasiswas',
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'jenis_kelamin' => 'nullable|string|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'tempat_lahir' => 'nullable|string|max:100',
            'agama' => 'nullable|string|max:50',
            'prodi_id' => 'required|exists:prodis,id',
            'angkatan' => 'nullable|integer',
            'status' => 'nullable|string|in:aktif,nonaktif,cuti,lulus',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Buat user account jika email tersedia
            $user = User::create([
                'name' => $request->nama,
                'email' => $request->email,
                'password' => Hash::make($request->password ?? $request->nim), // Default password adalah NIM jika password tidak diisi
                'nim' => $request->nim,
            ]);

            // Assign role mahasiswa
            $role = Role::where('name', 'mahasiswa')->first();

            if ($role) {
                $user->assignRole($role);
            }

            // Buat data mahasiswa
            $mahasiswa = Mahasiswa::create([
                'nim' => $request->nim,
                'nama' => $request->nama,
                'email' => $request->email,
                'telepon' => $request->telepon,
                'alamat' => $request->alamat,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tanggal_lahir' => $request->tanggal_lahir,
                'tempat_lahir' => $request->tempat_lahir,
                'agama' => $request->agama,
                'prodi_id' => $request->prodi_id,
                'user_id' => $user->id,
                'angkatan' => $request->angkatan ?? date('Y'),
                'status' => $request->status ?? 'aktif',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Mahasiswa created successfully',
                'mahasiswa' => $mahasiswa
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to create mahasiswa: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $mahasiswa = Mahasiswa::with(['prodi', 'user'])->findOrFail($id);

        return response()->json([
            'mahasiswa' => $mahasiswa
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
        $mahasiswa = Mahasiswa::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nim' => 'required|string|max:50|unique:mahasiswas,nim,' . $id,
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $mahasiswa->user_id,
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'jenis_kelamin' => 'nullable|string|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'tempat_lahir' => 'nullable|string|max:100',
            'agama' => 'nullable|string|max:50',
            'prodi_id' => 'required|exists:prodis,id',
            'angkatan' => 'nullable|integer',
            'status' => 'nullable|string|in:aktif,nonaktif,cuti,lulus',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Update user jika ada
            if ($mahasiswa->user_id) {
                $user = User::find($mahasiswa->user_id);
                if ($user) {
                    $user->name = $request->nama;
                    $user->email = $request->email;
                    $user->nim = $request->nim;

                    if ($request->filled('password')) {
                        $user->password = Hash::make($request->password);
                    }

                    $user->save();
                }
            }

            // Update mahasiswa
            $mahasiswa->nim = $request->nim;
            $mahasiswa->nama = $request->nama;
            $mahasiswa->email = $request->email;
            $mahasiswa->telepon = $request->telepon;
            $mahasiswa->alamat = $request->alamat;
            $mahasiswa->jenis_kelamin = $request->jenis_kelamin;
            $mahasiswa->tanggal_lahir = $request->tanggal_lahir;
            $mahasiswa->tempat_lahir = $request->tempat_lahir;
            $mahasiswa->agama = $request->agama;
            $mahasiswa->prodi_id = $request->prodi_id;
            $mahasiswa->angkatan = $request->angkatan;
            $mahasiswa->status = $request->status;
            $mahasiswa->save();

            DB::commit();

            return response()->json([
                'message' => 'Mahasiswa updated successfully',
                'mahasiswa' => $mahasiswa
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to update mahasiswa: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $mahasiswa = Mahasiswa::findOrFail($id);

        DB::beginTransaction();

        try {
            // Delete related user if exists
            if ($mahasiswa->user_id) {
                $user = User::find($mahasiswa->user_id);
                if ($user) {
                    $user->delete();
                }
            }

            // Delete mahasiswa
            $mahasiswa->delete();

            DB::commit();

            return response()->json([
                'message' => 'Mahasiswa deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to delete mahasiswa: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import mahasiswa from excel file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Store file temporarily
            $path = $request->file('file')->store('temp');

            // Process import
            $import = new MahasiswaImport();

            // We'll use try-catch to catch validation errors
            DB::beginTransaction();

            try {
                Excel::import($import, $path);

                // Delete temporary file
                Storage::delete($path);

                DB::commit();

                return response()->json([
                    'message' => 'Mahasiswa imported successfully',
                    'total_imported' => $import->getRowCount(),
                    'failures' => $import->failures()
                ]);
            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                DB::rollback();

                // Format validation errors
                $failures = $e->failures();
                $errors = [];

                foreach ($failures as $failure) {
                    $errors[] = [
                        'row' => $failure->row(),
                        'attribute' => $failure->attribute(),
                        'errors' => $failure->errors()
                    ];
                }

                // Delete temporary file
                Storage::delete($path);

                return response()->json([
                    'message' => 'Validation failed during import',
                    'errors' => $errors
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to import mahasiswa: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get mahasiswa statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function getStatistics()
    {
        // Total mahasiswa
        $totalMahasiswa = Mahasiswa::count();

        // Mahasiswa per prodi
        $mahasiswaPerProdi = Mahasiswa::selectRaw('prodi_id, count(*) as total')
            ->groupBy('prodi_id')
            ->with('prodi:id,nama,jenjang')
            ->get();

        // Mahasiswa per status
        $mahasiswaPerStatus = Mahasiswa::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get();

        // Mahasiswa per angkatan
        $mahasiswaPerAngkatan = Mahasiswa::selectRaw('angkatan, count(*) as total')
            ->groupBy('angkatan')
            ->orderBy('angkatan', 'desc')
            ->get();

        // Mahasiswa per jenis kelamin
        $mahasiswaPerJenisKelamin = Mahasiswa::selectRaw('jenis_kelamin, count(*) as total')
            ->groupBy('jenis_kelamin')
            ->get();

        return response()->json([
            'total' => $totalMahasiswa,
            'per_prodi' => $mahasiswaPerProdi,
            'per_status' => $mahasiswaPerStatus,
            'per_angkatan' => $mahasiswaPerAngkatan,
            'per_jenis_kelamin' => $mahasiswaPerJenisKelamin
        ]);
    }

    /**
     * Get mahasiswa for dropdown.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getForDropdown(Request $request)
    {
        $query = Mahasiswa::select('id', 'nim', 'nama', 'prodi_id');

        // Filter by prodi_id if provided
        if ($request->has('prodi_id') && $request->prodi_id) {
            $query->where('prodi_id', $request->prodi_id);
        }

        // Filter by status if provided (default to aktif)
        $status = $request->input('status', 'aktif');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Search by name or NIM
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('nim', 'like', '%' . $search . '%');
            });
        }

        // Sort by name
        $query->orderBy('nama', 'asc');

        // Get data with prodi information
        $mahasiswas = $query->with('prodi:id,nama')->get();

        return response()->json([
            'mahasiswas' => $mahasiswas
        ]);
    }
}
