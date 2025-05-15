<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Mail\LeaveStatusMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveService
{
    /**
     * Store a new leave request
     */
    public function store(array $data, User $user): LeaveRequest
    {
        DB::beginTransaction();
        try {
            // Format dates to match database structure
            $startDate = Carbon::parse($data['start_date'])->format('Y-m-d');
            $endDate = Carbon::parse($data['end_date'])->format('Y-m-d');

            $leaveRequest = LeaveRequest::create([
                'user_id' => $user->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $data['type'],
                'reason' => $data['reason'],
                'status' => 'pending',
                'admin_id' => null,
                'admin_remarks' => null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            
            Log::info('Leave request created successfully', [
                'id' => $leaveRequest->id,
                'user_id' => $user->id,
                'type' => $data['type']
            ]);

            return $leaveRequest->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store leave request: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update leave request status
     */
    public function updateStatus(LeaveRequest $leaveRequest, string $status, User $admin, string $remarks): LeaveRequest
    {
        DB::beginTransaction();
        try {
            // Add pre-update email logging
            Log::info('Preparing to update leave status and send email', [
                'leave_id' => $leaveRequest->id,
                'user_email' => $leaveRequest->user->email,
                'status' => $status,
                'admin_id' => $admin->id
            ]);

            // Update the leave request
            $updated = $leaveRequest->update([
                'status' => $status,
                'admin_id' => $admin->id,
                'admin_remarks' => $remarks
            ]);

            if (!$updated) {
                throw new \Exception('Failed to update leave request');
            }

            // Attempt to send email
            try {
                Mail::to($leaveRequest->user->email)
                    ->send(new LeaveStatusMail($leaveRequest->fresh()));
                
                Log::info('Leave status email sent successfully', [
                    'leave_id' => $leaveRequest->id,
                    'user_email' => $leaveRequest->user->email,
                    'user_name' => $leaveRequest->user->name
                ]);
            } catch (\Exception $emailError) {
                Log::error('Failed to send leave status email', [
                    'error' => $emailError->getMessage(),
                    'leave_id' => $leaveRequest->id,
                    'user_email' => $leaveRequest->user->email
                ]);
            }

            // Log the successful update
            Log::info('Leave status updated', [
                'leave_id' => $leaveRequest->id,
                'old_status' => $leaveRequest->getOriginal('status'),
                'new_status' => $status,
                'admin_id' => $admin->id
            ]);

            DB::commit();
            return $leaveRequest->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update leave status', [
                'error' => $e->getMessage(),
                'leave_id' => $leaveRequest->id
            ]);
            throw $e;
        }
    }

    /**
     * Check if dates overlap with existing leaves
     */
    public function hasOverlappingLeaves(User $user, string $startDate, string $endDate): bool
    {
        return LeaveRequest::where('user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })->exists();
    }
}