<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class LeavePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any leave requests.
     * Admins can view all, users can only view their own.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can access the leave list page
    }

    /**
     * Determine if user can view the specific leave request.
     * Admins can view all, users can only view their own requests.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->isAdmin() || $user->id === $leaveRequest->user_id;
    }

    /**
     * Determine if user can create leave requests.
     * Only non-admin users can create leave requests.
     */
    public function create(User $user): bool
    {
        return !$user->isAdmin();
    }

    /**
     * Determine if user can update the leave request.
     * Users can only update their own pending requests.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return !$user->isAdmin() && 
               $user->id === $leaveRequest->user_id && 
               $leaveRequest->status === 'pending';
    }

    /**
     * Determine if user can delete the leave request.
     * Users can only delete their own pending requests.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return !$user->isAdmin() && 
               $user->id === $leaveRequest->user_id && 
               $leaveRequest->status === 'pending';
    }

    /**
     * Determine if user can update status of the leave request.
     * Only users with admin role can approve/reject pending requests.
     */
    public function updateStatus(User $user, LeaveRequest $leaveRequest): bool
    {
        // Add policy check logging
        Log::info('Leave policy updateStatus check', [
            'user_id' => $user->id,
            'is_admin' => $user->hasRole('admin'),
            'leave_id' => $leaveRequest->id
        ]);

        // Before returning true/false, log the decision
        $canUpdate = $user->hasRole('admin');
        
        Log::info('Leave policy decision', [
            'can_update' => $canUpdate,
            'user_id' => $user->id
        ]);

        return $canUpdate;
    }

    /**
     * Determine if user can view admin remarks.
     * Both admins and request owners can view remarks.
     */
    public function viewRemarks(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->isAdmin() || $user->id === $leaveRequest->user_id;
    }
}