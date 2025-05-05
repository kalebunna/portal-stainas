<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AgendaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Agenda::with('user');

        // Filter berdasarkan status publikasi
        if ($request->has('is_published')) {
            $query->where('is_published', $request->is_published);
        }

        // Filter berdasarkan tanggal
        if ($request->has('start_date') && $request->start_date) {
            $query->where('waktu_mulai', '>=', Carbon::parse($request->start_date)->startOfDay());
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('waktu_mulai', '<=', Carbon::parse($request->end_date)->endOfDay());
        }

        // Filter hanya agenda yang akan datang
        if ($request->has('upcoming') && $request->upcoming) {
            $query->where('waktu_mulai', '>=', Carbon::now());
        }

        // Filter hanya agenda yang sedang berlangsung
        if ($request->has('ongoing') && $request->ongoing) {
            $now = Carbon::now();
            $query->where('waktu_mulai', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->where('waktu_selesai', '>=', $now)
                        ->orWhereNull('waktu_selesai');
                });
        }

        // Cari berdasarkan judul atau lokasi
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', '%' . $search . '%')
                    ->orWhere('lokasi', 'like', '%' . $search . '%')
                    ->orWhere('deskripsi', 'like', '%' . $search . '%');
            });
        }

        // Urutkan berdasarkan parameter
        $sortBy = $request->input('sort_by', 'waktu_mulai');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginasi
        $perPage = $request->input('per_page', 10);
        $agenda = $query->paginate($perPage);

        return response()->json([
            'agenda' => $agenda
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
            'lokasi' => 'nullable|string|max:255',
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'nullable|date|after_or_equal:waktu_mulai',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $agenda = new Agenda();
        $agenda->judul = $request->judul;
        $agenda->slug = Str::slug($request->judul) . '-' . time();
        $agenda->deskripsi = $request->deskripsi;
        $agenda->lokasi = $request->lokasi;
        $agenda->waktu_mulai = $request->waktu_mulai;
        $agenda->waktu_selesai = $request->waktu_selesai;
        $agenda->user_id = Auth::id();
        $agenda->is_published = $request->is_published ?? false;

        // Upload gambar jika ada
        if ($request->hasFile('gambar')) {
            $gambarPath = $request->file('gambar')->store('images/agenda', 'public');
            $agenda->gambar = $gambarPath;
        }

        $agenda->save();

        return response()->json([
            'message' => 'Agenda created successfully',
            'agenda' => $agenda
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
        $agenda = Agenda::with('user')->where('slug', $slug)->firstOrFail();

        return response()->json([
            'agenda' => $agenda
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
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'lokasi' => 'nullable|string|max:255',
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'nullable|date|after_or_equal:waktu_mulai',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $agenda = Agenda::findOrFail($id);

        // Update judul dan slug jika judul berubah
        if ($agenda->judul !== $request->judul) {
            $agenda->judul = $request->judul;
            $agenda->slug = Str::slug($request->judul) . '-' . time();
        }

        $agenda->deskripsi = $request->deskripsi;
        $agenda->lokasi = $request->lokasi;
        $agenda->waktu_mulai = $request->waktu_mulai;
        $agenda->waktu_selesai = $request->waktu_selesai;
        $agenda->is_published = $request->is_published ?? $agenda->is_published;

        // Upload gambar baru jika ada
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($agenda->gambar && Storage::disk('public')->exists($agenda->gambar)) {
                Storage::disk('public')->delete($agenda->gambar);
            }

            // Upload gambar baru
            $gambarPath = $request->file('gambar')->store('images/agenda', 'public');
            $agenda->gambar = $gambarPath;
        }

        $agenda->save();

        return response()->json([
            'message' => 'Agenda updated successfully',
            'agenda' => $agenda
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
        $agenda = Agenda::findOrFail($id);

        // Hapus gambar jika ada
        if ($agenda->gambar && Storage::disk('public')->exists($agenda->gambar)) {
            Storage::disk('public')->delete($agenda->gambar);
        }

        $agenda->delete();

        return response()->json([
            'message' => 'Agenda deleted successfully'
        ]);
    }

    /**
     * Get upcoming agenda.
     *
     * @param  int  $limit
     * @return \Illuminate\Http\Response
     */
    public function getUpcoming($limit = 5)
    {
        $agenda = Agenda::where('waktu_mulai', '>=', Carbon::now())
            ->where('is_published', true)
            ->orderBy('waktu_mulai', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'agenda' => $agenda
        ]);
    }

    /**
     * Get agenda by month.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getByMonth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2099',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $month = $request->month;
        $year = $request->year;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $agenda = Agenda::where('is_published', true)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('waktu_mulai', [$startDate, $endDate])
                    ->orWhereBetween('waktu_selesai', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('waktu_mulai', '<=', $startDate)
                            ->where('waktu_selesai', '>=', $endDate);
                    });
            })
            ->orderBy('waktu_mulai', 'asc')
            ->get();

        return response()->json([
            'agenda' => $agenda,
            'month' => $month,
            'year' => $year
        ]);
    }

    /**
     * Toggle publish status.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function togglePublish($id)
    {
        $agenda = Agenda::findOrFail($id);
        $agenda->is_published = !$agenda->is_published;
        $agenda->save();

        return response()->json([
            'message' => 'Agenda publish status toggled successfully',
            'is_published' => $agenda->is_published
        ]);
    }

    /**
     * Get agenda for calendar.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getForCalendar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $startDate = Carbon::parse($request->start);
        $endDate = Carbon::parse($request->end);

        $agenda = Agenda::where('is_published', true)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('waktu_mulai', [$startDate, $endDate])
                    ->orWhereBetween('waktu_selesai', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('waktu_mulai', '<=', $startDate)
                            ->where(function ($inner) use ($endDate) {
                                $inner->where('waktu_selesai', '>=', $endDate)
                                    ->orWhereNull('waktu_selesai');
                            });
                    });
            })
            ->get();

        // Format data for calendar
        $calendarEvents = $agenda->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->judul,
                'start' => $item->waktu_mulai,
                'end' => $item->waktu_selesai,
                'allDay' => Carbon::parse($item->waktu_mulai)->diffInHours(Carbon::parse($item->waktu_selesai)) >= 24,
                'location' => $item->lokasi,
                'description' => Str::limit(strip_tags($item->deskripsi), 100),
                'url' => '/agenda/' . $item->slug
            ];
        });

        return response()->json([
            'events' => $calendarEvents
        ]);
    }
}
