<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ToolJob;
use App\Models\User;
use Illuminate\Http\Request;

class AdminJobController extends Controller
{
    public function index(Request $request)
    {
        $query = ToolJob::with(['user', 'chatSession']);

        if ($search = $request->get('search')) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if ($toolType = $request->get('tool_type')) {
            $query->where('tool_type', $toolType);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $jobs = $query->latest()->paginate(25)->withQueryString();

        $toolTypes = ToolJob::distinct()->pluck('tool_type')->sort()->values();
        $statuses  = ['queued', 'running', 'succeeded', 'failed', 'cancelled'];

        return view('admin.jobs.index', compact('jobs', 'toolTypes', 'statuses'));
    }
}
