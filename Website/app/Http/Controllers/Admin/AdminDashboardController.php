<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ToolJob;
use App\Models\User;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Stats cards
        $totalUsers    = User::where('role', '!=', 'admin')->count();
        $activeUsers   = User::where('role', '!=', 'admin')
                             ->where('is_verified', true)->count();
        $totalJobs     = ToolJob::count();
        $jobsThisMonth = ToolJob::whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)->count();
        $totalCreditsUsed = User::sum('credits_used');
        $plansCount    = SubscriptionPlan::where('is_active', true)->count();

        // Users by plan
        $usersByPlan = SubscriptionPlan::withCount('users')
            ->orderBy('sort_order')->get();

        // Recent users
        $recentUsers = User::where('role', '!=', 'admin')
            ->with('plan')
            ->latest()
            ->take(8)
            ->get();

        // Recent jobs
        $recentJobs = ToolJob::with('user')
            ->latest()
            ->take(10)
            ->get();

        // Jobs by tool type (for chart)
        $jobsByTool = ToolJob::select('tool_type', DB::raw('count(*) as total'))
            ->groupBy('tool_type')
            ->orderByDesc('total')
            ->get();

        // Registrations last 7 days (for chart)
        $registrations = User::where('role', '!=', 'admin')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartDates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartDates->push([
                'date'  => now()->subDays($i)->format('M d'),
                'total' => $registrations[$date]->total ?? 0,
            ]);
        }

        // Job status breakdown
        $jobStatuses = ToolJob::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $aiSettings = [
            'tts_provider' => AppSetting::getValue('tts_provider', 'edge'),
            'quiz_ai_provider' => AppSetting::getValue('quiz_ai_provider', 'questgen'),
            'quiz_ai_model' => AppSetting::getValue('quiz_ai_model', 'gemini-2.5-flash'),
        ];

        return view('admin.dashboard', compact(
            'totalUsers', 'activeUsers', 'totalJobs', 'jobsThisMonth',
            'totalCreditsUsed', 'plansCount', 'usersByPlan',
            'recentUsers', 'recentJobs', 'jobsByTool',
            'chartDates', 'jobStatuses', 'aiSettings'
        ));
    }

    public function updateAiSettings(Request $request)
    {
        $validated = $request->validate([
            'tts_provider' => 'required|in:edge,gemini',
            'quiz_ai_provider' => 'required|in:questgen,gemini,chatgpt',
            'quiz_ai_model' => 'nullable|string|max:120',
        ]);

        AppSetting::setValue('tts_provider', $validated['tts_provider']);
        AppSetting::setValue('quiz_ai_provider', $validated['quiz_ai_provider']);
        AppSetting::setValue(
            'quiz_ai_model',
            $validated['quiz_ai_model'] ?: ($validated['quiz_ai_provider'] === 'chatgpt' ? config('services.ai.chatgpt_model', 'gpt-4o-mini') : 'gemini-2.5-flash')
        );

        return back()->with('success', 'AI settings updated successfully.');
    }
}
