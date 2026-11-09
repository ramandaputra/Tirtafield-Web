<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\Office;
use App\Models\Presensi;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class WebDashboardController extends Controller
{
    /**
     * Tampilan Splash Screen (Matching Flutter SplashScreen.dart)
     */
    public function splash()
    {
        return view('auth.splash');
    }

    /**
     * Tampilan Login Web
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('web.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Proses Login Web
     */
    public function doLogin(Request $request)
    {
        $request->validate([
            'nik' => 'required',
            'pin' => 'required',
        ]);

        $nik = trim($request->input('nik'));
        $pin = trim($request->input('pin'));

        $user = User::where('is_active', true)
            ->where(function ($q) use ($nik) {
                $q->where('nik', $nik)
                  ->orWhere('email', $nik);
            })
            ->first();

        if ($user && $user->verifySecret($pin)) {
            Auth::login($user, $request->boolean('remember'));
            Session::regenerate();
            return redirect()->intended(route('web.dashboard'))->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        return back()->withErrors([
            'nik' => 'NIP/NIK atau PIN yang Anda masukkan tidak sesuai.',
        ])->withInput($request->only('nik'));
    }

    /**
     * Switch Role Demo (Persis seperti Demo Switch Role pada Flutter main_shell.dart)
     */
    public function switchRole(string $role)
    {
        $nikMap = [
            'karyawan' => User::where('nik', 'EMP001')->exists() ? 'EMP001' : 'K202609',
            'direktur' => 'D001',
            'staffIT'  => 'P242101',
        ];

        $targetNik = $nikMap[$role] ?? 'P242101';
        $user = User::where('nik', $targetNik)->first()
             ?? User::where('role', $role)->first();

        if ($user) {
            Auth::login($user);
            Session::regenerate();
            return redirect()->route('web.dashboard')->with('success', 'Berhasil beralih ke tampilan role: ' . strtoupper($role));
        }

        return redirect()->route('web.dashboard')->withErrors(['error' => 'Akun untuk role tersebut tidak ditemukan.']);
    }

    /**
     * Logout Web
     */
    public function doLogout(Request $request)
    {
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();
        return redirect()->route('web.login')->with('info', 'Anda telah berhasil keluar.');
    }

    /**
     * Dashboard Utama (Direktur / Staff IT / Karyawan)
     */
    public function dashboard()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $user = Auth::user();

        // Cari kantor penempatan pengguna
        $userOffice = null;
        if (!empty($user->office_id)) {
            $userOffice = Office::find($user->office_id);
        }
        if (!$userOffice && !empty($user->office_name)) {
            $userOffice = Office::where('nama_kantor', 'like', '%' . $user->office_name . '%')->first();
        }
        if (!$userOffice) {
            $userOffice = Office::where('is_primary', true)->first() ?: Office::where('is_active', true)->first();
        }

        $offices = Office::where('is_active', true)->get();
        $systemSettings = SystemSetting::getAllSettings();

        // Normalisasi struktur operational_hours & geofencing agar identik dengan model Flutter
        $defaultDailySchedules = [
            ['day_of_week' => 1, 'day_name' => 'Senin', 'is_work_day' => true, 'masuk' => '08:00', 'istirahat_keluar' => '12:00', 'istirahat_masuk' => '13:00', 'pulang' => '17:00', 'toleransi_menit' => 15],
            ['day_of_week' => 2, 'day_name' => 'Selasa', 'is_work_day' => true, 'masuk' => '08:00', 'istirahat_keluar' => '12:00', 'istirahat_masuk' => '13:00', 'pulang' => '17:00', 'toleransi_menit' => 15],
            ['day_of_week' => 3, 'day_name' => 'Rabu', 'is_work_day' => true, 'masuk' => '08:00', 'istirahat_keluar' => '12:00', 'istirahat_masuk' => '13:00', 'pulang' => '17:00', 'toleransi_menit' => 15],
            ['day_of_week' => 4, 'day_name' => 'Kamis', 'is_work_day' => true, 'masuk' => '08:00', 'istirahat_keluar' => '12:00', 'istirahat_masuk' => '13:00', 'pulang' => '17:00', 'toleransi_menit' => 15],
            ['day_of_week' => 5, 'day_name' => 'Jumat', 'is_work_day' => true, 'masuk' => '08:00', 'istirahat_keluar' => '11:30', 'istirahat_masuk' => '13:30', 'pulang' => '17:00', 'toleransi_menit' => 15],
            ['day_of_week' => 6, 'day_name' => 'Sabtu', 'is_work_day' => false, 'masuk' => '08:00', 'istirahat_keluar' => '12:00', 'istirahat_masuk' => '12:30', 'pulang' => '13:00', 'toleransi_menit' => 15],
            ['day_of_week' => 7, 'day_name' => 'Minggu', 'is_work_day' => false, 'masuk' => '08:00', 'istirahat_keluar' => '12:00', 'istirahat_masuk' => '13:00', 'pulang' => '17:00', 'toleransi_menit' => 15],
        ];

        if (empty($systemSettings['operational_hours']['daily_schedules'])) {
            $systemSettings['operational_hours']['daily_schedules'] = $defaultDailySchedules;
        }

        // Tentukan jadwal jam operasional hari ini sesuai konfigurasi Superadmin
        $dayOfWeekIso = Carbon::now('Asia/Jakarta')->dayOfWeekIso; // 1 = Senin, ..., 7 = Minggu
        $todaySchedule = null;
        foreach ($systemSettings['operational_hours']['daily_schedules'] as $ds) {
            if (($ds['day_of_week'] ?? 0) === $dayOfWeekIso) {
                $todaySchedule = $ds;
                break;
            }
        }
        if (!$todaySchedule) {
            $todaySchedule = $defaultDailySchedules[$dayOfWeekIso - 1] ?? $defaultDailySchedules[0];
        }
        if (!isset($systemSettings['operational_hours']['auto_lock_outside'])) {
            $systemSettings['operational_hours']['auto_lock_outside'] = true;
        }

        if (empty($systemSettings['geofencing']['offices']) || count($systemSettings['geofencing']['offices']) === 0) {
            $systemSettings['geofencing']['offices'] = $offices->map(fn($o) => [
                'id'         => (string)$o->id,
                'name'       => $o->nama_kantor,
                'address'    => $o->alamat ?? '',
                'latitude'   => (float)$o->latitude,
                'longitude'  => (float)$o->longitude,
                'radius'     => (int)$o->radius,
                'is_primary' => (bool)$o->is_primary,
            ])->toArray();
        }
        if (empty($systemSettings['geofencing']['active_office_id'])) {
            $primary = collect($systemSettings['geofencing']['offices'])->firstWhere('is_primary', true);
            $systemSettings['geofencing']['active_office_id'] = $primary['id'] ?? ($systemSettings['geofencing']['offices'][0]['id'] ?? 'hq');
        }

        $nowJakarta = Carbon::now('Asia/Jakarta');
        $isDiniHari = ($nowJakarta->hour < 7);
        $yesterday = $nowJakarta->copy()->subDay()->toDateString();
        $isOvernightActive = false;

        // 4 Slot Presensi Pengguna Login Hari Ini
        $mySlots = [
            'masuk'           => null,
            'istirahatKeluar' => null,
            'istirahatMasuk'  => null,
            'pulang'          => null,
        ];
        if ($user) {
            $myRecords = Presensi::where(function ($q) use ($user) {
                    $q->where('pgw_id', $user->id)
                      ->orWhere('nik', $user->nik);
                })
                ->whereDate('tanggal', $today)
                ->get();

            foreach ($myRecords as $r) {
                if (array_key_exists($r->slot_type, $mySlots)) {
                    $mySlots[$r->slot_type] = $r->toSlotArray();
                }
            }

            // Karyawan yang lembur hingga ganti hari dan pulang saat dini hari
            if ($isDiniHari) {
                $yesterdayMasuk = Presensi::where(function ($q) use ($user) {
                        $q->where('pgw_id', $user->id)
                          ->orWhere('nik', $user->nik);
                    })
                    ->whereDate('tanggal', $yesterday)
                    ->where('slot_type', 'masuk')
                    ->first();

                $yesterdayPulang = Presensi::where(function ($q) use ($user) {
                        $q->where('pgw_id', $user->id)
                          ->orWhere('nik', $user->nik);
                    })
                    ->whereDate('tanggal', $yesterday)
                    ->where('slot_type', 'pulang')
                    ->first();

                $diniHariPulangToday = Presensi::where(function ($q) use ($user) {
                        $q->where('pgw_id', $user->id)
                          ->orWhere('nik', $user->nik);
                    })
                    ->whereDate('tanggal', $today)
                    ->where('slot_type', 'pulang')
                    ->whereTime('waktu', '<', '07:00:00')
                    ->first();

                if ($yesterdayMasuk && !$yesterdayPulang && !$diniHariPulangToday && empty($mySlots['pulang'])) {
                    $isOvernightActive = true;
                } elseif ($diniHariPulangToday && empty($mySlots['pulang'])) {
                    $mySlots['pulang'] = $diniHariPulangToday->toSlotArray();
                } elseif ($yesterdayPulang && empty($mySlots['pulang']) && Carbon::parse($yesterdayPulang->waktu)->isToday()) {
                    $mySlots['pulang'] = $yesterdayPulang->toSlotArray();
                }
            }
        }

        // Riwayat presensi & permohonan pribadi
        $myHistory = Presensi::where(function ($q) use ($user) {
                $q->where('pgw_id', $user->id)
                  ->orWhere('nik', $user->nik);
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc')
            ->take(20)
            ->get();

        $myApprovals = Presensi::where(function ($q) use ($user) {
                $q->where('pgw_id', $user->id)
                  ->orWhere('nik', $user->nik);
            })
            ->whereNotNull('approval_status')
            ->where('approval_status', '!=', '')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        // Pengajuan approval diambil langsung dari database tirtafield tabel presensi
        // 1. Pending: Semua pengajuan yang masih menunggu persetujuan (termasuk dari lembur dini hari atau hari sebelumnya)
        $pendingApprovals = Presensi::with('user.office')
            ->whereRaw('LOWER(approval_status) = ?', ['pending'])
            ->orderBy('id', 'desc')
            ->get();

        // 2. Disetujui (Approved): Setelah berganti hari, data tersimpan di database tetapi tidak muncul lagi di tampilan admin/superadmin (hanya tampil jika disetujui/tercatat hari ini)
        $approvedApprovals = Presensi::with('user.office')
            ->whereRaw('LOWER(approval_status) = ?', ['approved'])
            ->where(function ($q) use ($today) {
                $q->whereDate('reviewed_at', $today)
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereNull('reviewed_at')
                          ->whereDate('tanggal', $today);
                  });
            })
            ->orderBy('id', 'desc')
            ->get();

        // 3. Ditolak (Rejected): Setelah berganti hari, data tersimpan di database tetapi tidak muncul lagi di tampilan admin/superadmin (hanya tampil jika ditolak/tercatat hari ini)
        $rejectedApprovals = Presensi::with('user.office')
            ->whereRaw('LOWER(approval_status) = ?', ['rejected'])
            ->where(function ($q) use ($today) {
                $q->whereDate('reviewed_at', $today)
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereNull('reviewed_at')
                          ->whereDate('tanggal', $today);
                  });
            })
            ->orderBy('id', 'desc')
            ->get();

        // Data Manajerial & Monitoring
        $terlambatHariIni = Presensi::whereDate('tanggal', $today)->where('slot_type', 'masuk')->where('status', 'LIKE', '%Terlambat%')->count();
        $stats = [
            'total_karyawan'     => User::where('is_active', true)->where('role', 'karyawan')->count(),
            'hadir_hari_ini'     => Presensi::whereDate('tanggal', $today)->where('slot_type', 'masuk')->count(),
            'tepat_waktu'        => Presensi::whereDate('tanggal', $today)->where('slot_type', 'masuk')->where('status', 'NOT LIKE', '%Terlambat%')->count(),
            'terlambat_hari_ini' => $terlambatHariIni,
            'terlambat'          => $terlambatHariIni,
            'pending_approval'   => $pendingApprovals->count(),
            'approved_count'     => $approvedApprovals->count(),
            'rejected_count'     => $rejectedApprovals->count(),
        ];

        // Daftar aktivitas presensi hari ini (termasuk kepulangan lembur yang dicatat dini hari ini)
        $recentAttendances = Presensi::with('user.office')
            ->where(function ($q) use ($today) {
                $q->whereDate('tanggal', $today)
                  ->orWhereDate('waktu', $today);
            })
            ->orderBy('waktu', 'desc')
            ->take(25)
            ->get();

        $allEmployees = User::where('is_active', true)->orderBy('id', 'asc')->get();
        $bidangList = Bidang::with('seksi')->orderBy('urutan', 'asc')->get();

        return view('dashboard.index', compact(
            'stats',
            'recentAttendances',
            'pendingApprovals',
            'approvedApprovals',
            'rejectedApprovals',
            'today',
            'mySlots',
            'myHistory',
            'myApprovals',
            'userOffice',
            'offices',
            'systemSettings',
            'allEmployees',
            'bidangList',
            'todaySchedule',
            'isOvernightActive'
        ));
    }

    /**
     * Halaman Manajemen Pegawai
     */
    public function employees(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $q = trim($request->input('q', ''));
        $role = trim($request->input('role', ''));
        $department = trim($request->input('department', ''));

        $query = User::with('office')->where('is_active', true);

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%")
                    ->orWhere('department', 'like', "%{$q}%")
                    ->orWhere('position', 'like', "%{$q}%");
            });
        }

        if (!empty($role)) {
            $query->where('role', $role);
        }

        if (!empty($department)) {
            $query->where('department', $department);
        }

        $employees = $query->orderBy('role', 'asc')->orderBy('name', 'asc')->paginate(15);
        $offices = Office::where('is_active', true)->get();
        $bidangList = Bidang::with('seksi')->orderBy('urutan', 'asc')->get();

        return view('employees.index', compact('employees', 'offices', 'bidangList', 'q', 'role', 'department'));
    }

    /**
     * Halaman Manajemen Approval
     */
    public function approvals(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $status = $request->input('status', 'all');
        $query = Presensi::with('user.office');
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        if ($status === 'pending') {
            $query->whereRaw('LOWER(approval_status) = ?', ['pending']);
        } elseif ($status === 'approved') {
            $query->whereRaw('LOWER(approval_status) = ?', ['approved'])
                  ->where(function ($q) use ($today) {
                      $q->whereDate('reviewed_at', $today)
                        ->orWhere(function ($sub) use ($today) {
                            $sub->whereNull('reviewed_at')
                                ->whereDate('tanggal', $today);
                        });
                  });
        } elseif ($status === 'rejected') {
            $query->whereRaw('LOWER(approval_status) = ?', ['rejected'])
                  ->where(function ($q) use ($today) {
                      $q->whereDate('reviewed_at', $today)
                        ->orWhere(function ($sub) use ($today) {
                            $sub->whereNull('reviewed_at')
                                ->whereDate('tanggal', $today);
                        });
                  });
        } else {
            // 'all': semua yang pending (termasuk hari sebelumnya), dan approved/rejected hanya untuk hari ini
            $query->where(function ($q) use ($today) {
                $q->whereRaw('LOWER(approval_status) = ?', ['pending'])
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereIn('approval_status', ['approved', 'rejected'])
                          ->where(function ($dateQ) use ($today) {
                              $dateQ->whereDate('reviewed_at', $today)
                                    ->orWhere(function ($nullQ) use ($today) {
                                        $nullQ->whereNull('reviewed_at')
                                              ->whereDate('tanggal', $today);
                                    });
                          });
                  });
            });
        }

        $approvals = $query->orderBy('id', 'desc')->paginate(15);
        $userOffice = Office::where('is_active', true)->first();

        return view('approvals.index', compact('approvals', 'status', 'userOffice'));
    }

    /**
     * Halaman Pengaturan Sistem (4 Slot Jam Kerja & Geofencing)
     */
    public function settings()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $settings = SystemSetting::getAllSettings();
        $offices = Office::where('is_active', true)->get();

        return view('settings.index', compact('settings', 'offices'));
    }
}

