<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SecurityLog;

class SecurityLogController extends Controller
{
    public function index()
    {
        // Ambil data asli dari database, ubah format tanggalnya biar rapi
        $logs = SecurityLog::latest()->get()->map(function($log) {
            return [
                'id' => $log->id,
                'actor' => $log->actor,
                'role' => $log->role,
                'action' => $log->action,
                'desc' => $log->desc,
                'status' => $log->status,
                'time' => $log->created_at->diffForHumans() // Contoh: "2 menit yang lalu"
            ];
        });

        return Inertia::render('SuperAdmin/SecurityLogs', [
            'logs' => $logs
        ]);
    }
}