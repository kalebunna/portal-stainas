<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function index()
    {
        $profile = Profile::first();

        return response()->json([
            'profile' => $profile
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_kampus' => 'required|string|max:255',
            'singkatan' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'visi' => 'nullable|string',
            'misi' => 'nullable|string',
            'akreditasi' => 'nullable|string|max:50',
            'sejarah' => 'nullable|string',
            'maps_embed' => 'nullable|string',
            'rektor_name' => 'nullable|string|max:255',
            'rektor_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'sambutan_rektor' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $profile = Profile::first();
        if (!$profile) {
            $profile = new Profile();
        }

        // Mengisi data dari request
        $profile->nama_kampus = $request->nama_kampus;
        $profile->singkatan = $request->singkatan;
        $profile->deskripsi = $request->deskripsi;
        $profile->alamat = $request->alamat;
        $profile->telepon = $request->telepon;
        $profile->email = $request->email;
        $profile->website = $request->website;
        $profile->visi = $request->visi;
        $profile->misi = $request->misi;
        $profile->akreditasi = $request->akreditasi;
        $profile->sejarah = $request->sejarah;
        $profile->maps_embed = $request->maps_embed;
        $profile->rektor_name = $request->rektor_name;
        $profile->sambutan_rektor = $request->sambutan_rektor;

        // Menangani upload logo jika ada
        if ($request->hasFile('logo')) {
            // Hapus file lama jika ada
            if ($profile->logo && Storage::disk('public')->exists($profile->logo)) {
                Storage::disk('public')->delete($profile->logo);
            }

            $logoPath = $request->file('logo')->store('images/profile', 'public');
            $profile->logo = $logoPath;
        }

        // Menangani upload foto rektor jika ada
        if ($request->hasFile('rektor_photo')) {
            // Hapus file lama jika ada
            if ($profile->rektor_photo && Storage::disk('public')->exists($profile->rektor_photo)) {
                Storage::disk('public')->delete($profile->rektor_photo);
            }

            $rektorPhotoPath = $request->file('rektor_photo')->store('images/profile', 'public');
            $profile->rektor_photo = $rektorPhotoPath;
        }

        $profile->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile
        ]);
    }
}
