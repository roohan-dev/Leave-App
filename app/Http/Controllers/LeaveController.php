<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Services\LeaveService;
use App\Http\Requests\StoreLeaveRequest;
use App\Http\Requests\UpdateLeaveRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LeaveController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(
        private LeaveService $leaveService
    ) {}

    /**
     * Display a listing of the leave requests.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Determine which component to render based on the route
            $component = $request->route()->getName() === 'dashboard' ? 'Dashboard' : 'Leave/Index';
            
            // Build the query
            $query = LeaveRequest::with(['user:id,name,email']);
            
            // Filter leaves for non-admin users
            if (!$user->hasRole('admin')) {
                $query->where('user_id', $user->id);
            }

            $leaves = $query->latest()->get();

            // Calculate leave statistics based on user role
            if ($user->hasRole('admin')) {
                $leaveStats = [
                    'pending' => LeaveRequest::where('status', 'pending')->count(),
                    'approved' => LeaveRequest::where('status', 'approved')->count(),
                    'rejected' => LeaveRequest::where('status', 'rejected')->count(),
                ];
            } else {
                $leaveStats = [
                    'pending' => LeaveRequest::where('user_id', $user->id)
                                           ->where('status', 'pending')
                                           ->count(),
                    'approved' => LeaveRequest::where('user_id', $user->id)
                                            ->where('status', 'approved')
                                            ->count(),
                    'rejected' => LeaveRequest::where('user_id', $user->id)
                                            ->where('status', 'rejected')
                                            ->count(),
                ];
            }

            return Inertia::render($component, [
                'leaves' => $leaves,
                'isAdmin' => $user->hasRole('admin'),
                'leaveStats' => $leaveStats,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in LeaveController@index: ' . $e->getMessage());
            return back()->with('error', 'Error loading data.');
        }
    }

    /**
     * Show the form for creating a new leave request.
     */
    public function create()
    {
        try {
            $this->authorize('create', LeaveRequest::class);

            return $this->inertiaRender('Leave/Index', [
                'leaveTypes' => [
                    'sick' => 'Sick Leave',
                    'vacation' => 'Vacation',
                    'personal' => 'Personal Leave'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->error('Unable to create leave request.');
        }
    }

    /**
     * Store a newly created leave request.
     */
    public function store(StoreLeaveRequest $request)
    {
        try {
            $validated = $request->validated();

            // Check for overlapping leaves
            if ($this->leaveService->hasOverlappingLeaves(
                $request->user(),
                $validated['start_date'],
                $validated['end_date']
            )) {
                return $this->error('You have overlapping leave requests for these dates.');
            }

            $leaveRequest = $this->leaveService->store($validated, $request->user());


            return $this->success(
                ['leave' => $leaveRequest],
                'Leave request submitted successfully.'
            );
        } catch (\Exception $e) {
            Log::error('Leave request creation failed: ' . $e->getMessage());
            return $this->error('Failed to submit leave request. Please try again.');
        }
    }

    /**
     * Display the specified leave request.
     */
    public function show(LeaveRequest $leaveRequest)
    {
        try {
            $this->authorize('view', $leaveRequest);

            $leaveRequest->load(['user' => fn($q) => $q->select('id', 'name', 'email')]);

            return $this->inertiaRender('Leave/Show', [
                'leave' => $leaveRequest,
                'can' => [
                    'update' => Auth::user()->can('update', $leaveRequest),
                    'delete' => Auth::user()->can('delete', $leaveRequest),
                    'manage' => Auth::user()->isAdmin(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->error('Unable to view leave request.');
        }
    }

    /**
     * Show the form for editing the leave request.
     */
    public function edit(LeaveRequest $leaveRequest)
    {
        try {
            $this->authorize('update', $leaveRequest);

            return $this->inertiaRender('Leave/Edit', [
                'leave' => $leaveRequest,
                'leaveTypes' => config('leave.types'),
                'maxUploadSize' => config('leave.max_upload_size'),
            ]);
        } catch (\Exception $e) {
            return $this->error('Unable to edit leave request.');
        }
    }

    /**
     * Update the specified leave request.
     */
    public function update(UpdateLeaveRequest $request, LeaveRequest $leaveRequest)
    {
        try {
            $this->authorize('update', $leaveRequest);
            
            $validated = $request->validated();
            
            // Handle file upload if present
            if ($request->hasFile('documents')) {
                // Delete old document if exists
                if ($leaveRequest->documents) {
                    Storage::disk('public')->delete($leaveRequest->documents);
                }
                
                $validated['documents'] = $request->file('documents')
                    ->store('leave-documents', 'public');
            }

            $leaveRequest->update($validated);

            return $this->success(
                ['leave' => $leaveRequest], 
                'Leave request updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update leave request.');
        }
    }

    /**
     * Remove the specified leave request.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = auth()->user();
            
            // Add detailed logging
            Log::info('Attempting leave request deletion', [
                'user_id' => $user->id,
                'is_admin' => $user->hasRole('admin'),
                'roles' => $user->getRoleNames(),
                'leave_id' => $id
            ]);

            $leaveRequest = LeaveRequest::findOrFail($id);
            
            // Add explicit admin check
            if (!$user->hasRole('admin') && $leaveRequest->user_id !== $user->id) {
                Log::warning('Unauthorized user attempted to delete leave request', [
                    'user_id' => $user->id,
                    'leave_id' => $id
                ]);
                throw new \Illuminate\Auth\Access\AuthorizationException('You are not authorized to delete this leave request.');
            }
            
            $leaveRequest->delete();

            // Return redirect with success flash message
            return redirect()->route('dashboard')->with([
                'success' => true,
                'message' => 'Leave request deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting leave request: ' . $e->getMessage());
            
            // Return redirect with error flash message
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Error deleting leave request.'
            ]);
        }
    }

    /**
     * Update the status of the leave request.
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = auth()->user();
            
            // Add detailed logging
            Log::info('Attempting leave status update', [
                'user_id' => $user->id,
                'is_admin' => $user->hasRole('admin'),
                'roles' => $user->getRoleNames(),
                'leave_id' => $id
            ]);

            $leaveRequest = LeaveRequest::findOrFail($id);
            
            // Add explicit admin check before policy
            if (!$user->hasRole('admin')) {
                Log::warning('Non-admin user attempted to update leave status', [
                    'user_id' => $user->id
                ]);
                throw new \Illuminate\Auth\Access\AuthorizationException('You must be an admin to perform this action.');
            }
            
            //$this->authorize('updateStatus', $leaveRequest);
            
            $validated = $request->validate([
                'status' => 'required|in:approve,reject',
                'admin_remarks' => 'required|string|max:255',
            ]);

            $status = $validated['status'] === 'approve' ? 'approved' : 'rejected';

            $updatedLeave = $this->leaveService->updateStatus(
                $leaveRequest,
                $status,
                auth()->user(),
                $validated['admin_remarks']
            );

            // Instead of Inertia::render, use redirect with flash data
            return redirect()->route('dashboard')->with([
                'success' => true,
                'message' => "Leave request {$status} successfully",
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating leave status: ' . $e->getMessage());
            
            // Return redirect with error flash message
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Error updating leave status.'
            ]);
        }
    }
}