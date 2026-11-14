<?php

namespace App\Http\Controllers;

use App\Models\ApprovalTask;
use App\Models\AuditLog;
use App\Models\ServiceRequest;
use App\Models\WorkflowTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $myRequests = ServiceRequest::query()
            ->where('requester_id', $user->getKey())
            ->with('requestType', 'requester', 'department')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $myRequestCounts = ServiceRequest::query()
            ->where('requester_id', $user->getKey())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingApprovals = ApprovalTask::query()
            ->where('assigned_approver_id', $user->getKey())
            ->where('status', 'pending')
            ->with('workflowInstance.request.requestType', 'workflowInstance.request.requester', 'workflowInstance.request.department', 'stage')
            ->orderBy('due_at')
            ->limit(6)
            ->get();

        $overdueCount = ApprovalTask::query()
            ->where('assigned_approver_id', $user->getKey())
            ->where('status', 'pending')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $completedApprovals = ApprovalTask::query()
            ->where('assigned_approver_id', $user->getKey())
            ->whereIn('status', ['approved', 'rejected'])
            ->where('completed_at', '>=', now()->subDays(30))
            ->count();

        $statusDistribution = ServiceRequest::query()
            ->when(! $user->hasPermission('request.view.any'), fn ($query) => $query->where('requester_id', $user->getKey()))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn ($count, $status) => [(string) $status => (int) $count]);

        $openRequests = ServiceRequest::query()
            ->when(! $user->hasPermission('request.view.any'), fn ($query) => $query->where('requester_id', $user->getKey()))
            ->whereIn('status', ['submitted', 'under_review', 'revision_required'])
            ->count();

        $approvedThisMonth = ServiceRequest::query()
            ->when(! $user->hasPermission('request.view.any'), fn ($query) => $query->where('requester_id', $user->getKey()))
            ->where('status', 'approved')
            ->where('completed_at', '>=', now()->startOfMonth())
            ->count();

        $revisionRequired = ServiceRequest::query()
            ->where('requester_id', $user->getKey())
            ->where('status', 'revision_required')
            ->count();

        $draftCount = ServiceRequest::query()
            ->where('requester_id', $user->getKey())
            ->where('status', 'draft')
            ->count();

        $activeWorkflows = WorkflowTemplate::query()->where('is_active', true)->count();

        $recentActivity = null;

        if ($user->hasPermission('audit.view')) {
            $recentActivity = AuditLog::query()->with('actor')->latest('id')->limit(8)->get();
        }

        $statusTotal = max($statusDistribution->sum(), 1);

        return view('dashboard', [
            'myRequests' => $myRequests,
            'myRequestCounts' => $myRequestCounts,
            'pendingApprovals' => $pendingApprovals,
            'overdueCount' => $overdueCount,
            'completedApprovals' => $completedApprovals,
            'statusDistribution' => $statusDistribution,
            'statusTotal' => $statusTotal,
            'openRequests' => $openRequests,
            'approvedThisMonth' => $approvedThisMonth,
            'revisionRequired' => $revisionRequired,
            'draftCount' => $draftCount,
            'activeWorkflows' => $activeWorkflows,
            'recentActivity' => $recentActivity,
            'isApprover' => $user->hasPermission('approval.process'),
            'viewingAll' => $user->hasPermission('request.view.any'),
        ]);
    }
}
