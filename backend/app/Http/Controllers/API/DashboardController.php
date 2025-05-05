<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Models\Pengumuman;
use App\Models\Agenda;
use App\Models\Prodi;
use App\Models\Mahasiswa;
use App\Models\KaryaMahasiswa;
use App\Models\Kerjasama;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function getStats()
    {
        // Hitung total data
        $totalBerita = Berita::count();
        $totalPengumuman = Pengumuman::count();
        $totalAgenda = Agenda::count();
        $totalMahasiswa = Mahasiswa::count();
        $totalProdi = Prodi::count();
        $totalKaryaMahasiswa = KaryaMahasiswa::count();
        $totalKerjasama = Kerjasama::count();
        $totalUsers = User::count();

        // Hitung data yang ditambahkan bulan ini
        $startOfMonth = Carbon::now()->startOfMonth();
        $beritaBulanIni = Berita::where('created_at', '>=', $startOfMonth)->count();
        $pengumumanBulanIni = Pengumuman::where('created_at', '>=', $startOfMonth)->count();
        $agendaBulanIni = Agenda::where('created_at', '>=', $startOfMonth)->count();
        $mahasiswaBulanIni = Mahasiswa::where('created_at', '>=', $startOfMonth)->count();
        $karyaBulanIni = KaryaMahasiswa::where('created_at', '>=', $startOfMonth)->count();
        $kerjasamaBulanIni = Kerjasama::where('created_at', '>=', $startOfMonth)->count();

        // Berita terpopuler
        $popularBerita = Berita::orderBy('views', 'desc')
            ->where('is_published', true)
            ->limit(5)
            ->get(['id', 'judul', 'views', 'published_at']);

        // Agenda yang akan datang
        $upcomingAgenda = Agenda::where('waktu_mulai', '>=', Carbon::now())
            ->where('is_published', true)
            ->orderBy('waktu_mulai', 'asc')
            ->limit(5)
            ->get(['id', 'judul', 'waktu_mulai', 'lokasi']);

        // Pengumuman aktif
        $activeAnnouncements = Pengumuman::where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', Carbon::now()->toDateString());
            })
            ->where('tanggal_mulai', '<=', Carbon::now()->toDateString())
            ->orderBy('tanggal_mulai', 'desc')
            ->limit(5)
            ->get(['id', 'judul', 'tanggal_mulai', 'tanggal_selesai', 'tipe']);

        // Statistik mahasiswa per prodi
        $mahasiswaPerProdi = Mahasiswa::selectRaw('prodi_id, count(*) as total')
            ->groupBy('prodi_id')
            ->with('prodi:id,nama,jenjang')
            ->get();

        // Statistik mahasiswa berdasarkan status
        $mahasiswaPerStatus = Mahasiswa::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get();

        // Statistik mahasiswa berdasarkan tahun angkatan
        $mahasiswaPerAngkatan = Mahasiswa::selectRaw('angkatan, count(*) as total')
            ->groupBy('angkatan')
            ->orderBy('angkatan', 'desc')
            ->limit(5)
            ->get();

        // Karya mahasiswa terbaru
        $latestKarya = KaryaMahasiswa::with('mahasiswa:id,nama,nim,prodi_id', 'mahasiswa.prodi:id,nama')
            ->where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'judul', 'jenis', 'mahasiswa_id', 'created_at']);

        return response()->json([
            'counts' => [
                'berita' => $totalBerita,
                'pengumuman' => $totalPengumuman,
                'agenda' => $totalAgenda,
                'mahasiswa' => $totalMahasiswa,
                'prodi' => $totalProdi,
                'karya_mahasiswa' => $totalKaryaMahasiswa,
                'kerjasama' => $totalKerjasama,
                'users' => $totalUsers
            ],
            'monthly_additions' => [
                'berita' => $beritaBulanIni,
                'pengumuman' => $pengumumanBulanIni,
                'agenda' => $agendaBulanIni,
                'mahasiswa' => $mahasiswaBulanIni,
                'karya_mahasiswa' => $karyaBulanIni,
                'kerjasama' => $kerjasamaBulanIni
            ],
            'popular_berita' => $popularBerita,
            'upcoming_agenda' => $upcomingAgenda,
            'active_announcements' => $activeAnnouncements,
            'mahasiswa_per_prodi' => $mahasiswaPerProdi,
            'mahasiswa_per_status' => $mahasiswaPerStatus,
            'mahasiswa_per_angkatan' => $mahasiswaPerAngkatan,
            'latest_karya' => $latestKarya
        ]);
    }

    /**
     * Get activity logs for dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getActivityLogs(Request $request)
    {
        // If you use activity logs package like spatie/laravel-activitylog
        // Example implementation:
        /*
        $logs = \Spatie\Activitylog\Models\Activity::latest()
                                                 ->limit(20)
                                                 ->get();
        */

        // Basic simulation of activity logs
        $activityLogs = collect([
            [
                'log_name' => 'default',
                'description' => 'User admin menambahkan Berita baru',
                'subject_type' => 'App\Models\Berita',
                'subject_id' => 1,
                'causer_type' => 'App\Models\User',
                'causer_id' => 1,
                'properties' => ['title' => 'Berita Terbaru'],
                'created_at' => Carbon::now()->subHours(1)
            ],
            [
                'log_name' => 'default',
                'description' => 'User admin mengubah Pengumuman',
                'subject_type' => 'App\Models\Pengumuman',
                'subject_id' => 1,
                'causer_type' => 'App\Models\User',
                'causer_id' => 1,
                'properties' => ['title' => 'Pengumuman Penting'],
                'created_at' => Carbon::now()->subHours(2)
            ],
            [
                'log_name' => 'default',
                'description' => 'User admin menghapus Agenda',
                'subject_type' => 'App\Models\Agenda',
                'subject_id' => 1,
                'causer_type' => 'App\Models\User',
                'causer_id' => 1,
                'properties' => ['title' => 'Agenda Lama'],
                'created_at' => Carbon::now()->subHours(3)
            ],
            [
                'log_name' => 'default',
                'description' => 'User admin menambahkan Mahasiswa baru',
                'subject_type' => 'App\Models\Mahasiswa',
                'subject_id' => 1,
                'causer_type' => 'App\Models\User',
                'causer_id' => 1,
                'properties' => ['name' => 'Nama Mahasiswa'],
                'created_at' => Carbon::now()->subHours(4)
            ],
            [
                'log_name' => 'default',
                'description' => 'User admin menyetujui Karya Mahasiswa',
                'subject_type' => 'App\Models\KaryaMahasiswa',
                'subject_id' => 1,
                'causer_type' => 'App\Models\User',
                'causer_id' => 1,
                'properties' => ['title' => 'Karya Ilmiah'],
                'created_at' => Carbon::now()->subHours(5)
            ]
        ]);

        return response()->json([
            'activity_logs' => $activityLogs
        ]);
    }

    /**
     * Get quick summary for dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function getQuickSummary()
    {
        // Count pending karya mahasiswa for approval
        $pendingKarya = KaryaMahasiswa::where('is_published', false)
            ->whereNull('approved_at')
            ->count();

        // Count upcoming events this week
        $endOfWeek = Carbon::now()->endOfWeek();
        $upcomingEventsThisWeek = Agenda::where('waktu_mulai', '>=', Carbon::now())
            ->where('waktu_mulai', '<=', $endOfWeek)
            ->where('is_published', true)
            ->count();

        // Count active announcements
        $activeAnnouncements = Pengumuman::where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', Carbon::now()->toDateString());
            })
            ->where('tanggal_mulai', '<=', Carbon::now()->toDateString())
            ->count();

        // Total views of all berita
        $totalBeritaViews = Berita::sum('views');

        return response()->json([
            'pending_karya' => $pendingKarya,
            'upcoming_events_this_week' => $upcomingEventsThisWeek,
            'active_announcements' => $activeAnnouncements,
            'total_berita_views' => $totalBeritaViews
        ]);
    }
}
