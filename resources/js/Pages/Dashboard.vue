<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LeaveStatusBadge from '@/Components/LeaveStatusBadge.vue';
import InfoIcon from '@/Components/InfoIcon.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
const leaveTypeLabels = {
    sick: 'Sick Leave',
    vacation: 'Vacation Leave',
    personal: 'Personal Leave',
    emergency: 'Emergency Leave',
    marriage: 'Marriage Leave',
    maternity: 'Maternity Leave'
};
const calculateDays = (start, end) => {
    const startDate = new Date(start);
    const endDate = new Date(end);
    const diffTime = Math.abs(endDate - startDate);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
};
const formatDate = (dateString) => {
    return new Date(dateString).toISOString().split('T')[0];
};
const getLeaveTypeLabel = (type) => {
    return leaveTypeLabels[type] || type;
};
const props = defineProps({
    leaveStats: {
        type: Object,
        required: true
    },
    isAdmin: {
        type: Boolean,
        default: false
    },
    leaves: {
        type: Array,
        default: () => []
    }
});
// Add this for debugging
console.log('Props:', {
    isAdmin: props.isAdmin,
    leavesCount: props.leaves.length,
    leaves: props.leaves
});
const showActionModal = ref(false);
const selectedLeave = ref(null);
const actionType = ref('');
const adminRemarks = ref('');
// Add these new refs for date filtering
const startDateFilter = ref('');
const endDateFilter = ref('');
const showFilterModal = ref(false);
// Add new ref for edit modal
const showEditModal = ref(false);
// Add new ref for search query
const searchQuery = ref('');
// Change the default sort column and direction
const sortColumn = ref('status'); // Changed from 'user.name' to 'status'
const sortDirection = ref('asc'); // Default sort direction

// Add status priority mapping
const statusPriority = {
    'pending': 1,
    'approved': 2,
    'rejected': 3
};

// Add sorting function
const toggleSort = (column) => {
    if (sortColumn.value === column) {
        // If clicking the same column, toggle direction
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        // If clicking a new column, set it with ascending direction
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

// Update your filteredLeaves computed property to include sorting
const filteredLeaves = computed(() => {
    let leaves = props.leaves;
    
    // Apply search if query exists
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        leaves = leaves.filter(leave => 
            leave.user.name.toLowerCase().includes(query) ||    // Search by employee name
            getLeaveTypeLabel(leave.type).toLowerCase().includes(query) || // Search by leave type
            leave.status.toLowerCase().includes(query)          // Search by status
        );
    }
    
    // Apply sorting
    leaves = [...leaves].sort((a, b) => {
        let aValue, bValue;
        
        if (sortColumn.value === 'status') {
            // Use status priority for sorting
            aValue = statusPriority[a.status] || 999;
            bValue = statusPriority[b.status] || 999;
        } else if (sortColumn.value === 'days') {
            // Calculate days for comparison
            aValue = calculateDays(a.start_date, a.end_date);
            bValue = calculateDays(b.start_date, b.end_date);
        } else if (sortColumn.value.includes('.')) {
            const [parent, child] = sortColumn.value.split('.');
            aValue = a[parent][child];
            bValue = b[parent][child];
        } else {
            aValue = a[sortColumn.value];
            bValue = b[sortColumn.value];
        }

        // Handle special cases like dates
        if (sortColumn.value === 'start_date' || sortColumn.value === 'end_date') {
            aValue = new Date(aValue);
            bValue = new Date(bValue);
        }

        // For numerical values (like days and status priority)
        if (sortColumn.value === 'days' || sortColumn.value === 'status') {
            return sortDirection.value === 'asc' ? aValue - bValue : bValue - aValue;
        }

        // For other values, use string comparison
        if (sortDirection.value === 'asc') {
            return aValue > bValue ? 1 : -1;
        }
        return aValue < bValue ? 1 : -1;
    });

    // Apply existing date filters
    if (!startDateFilter.value && !endDateFilter.value) return leaves;
    
    return leaves.filter(leave => {
        const leaveStart = new Date(leave.start_date);
        const leaveEnd = new Date(leave.end_date);
        const filterStart = startDateFilter.value ? new Date(startDateFilter.value) : null;
        const filterEnd = endDateFilter.value ? new Date(endDateFilter.value) : null;

        if (filterStart && filterEnd) {
            return leaveStart >= filterStart && leaveEnd <= filterEnd;
        } else if (filterStart) {
            return leaveStart >= filterStart;
        } else if (filterEnd) {
            return leaveEnd <= filterEnd;
        }
        return true;
    });
});
// Add clear filter function
const clearFilters = () => {
    startDateFilter.value = '';
    endDateFilter.value = '';
    showFilterModal.value = false;
};
const handleAction = (leave, action) => {
    selectedLeave.value = leave;
    actionType.value = action; // Will be 'approve' or 'reject'
    showActionModal.value = true;
};
const submitAction = () => {
    if (!selectedLeave.value) return;
    router.put(route('leave-requests.update-status', { id: selectedLeave.value.id }), {
        status: actionType.value, // Will send 'approve' or 'reject'
        admin_remarks: adminRemarks.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showActionModal.value = false;
            adminRemarks.value = '';
            selectedLeave.value = null;
            router.reload({ only: ['leaves', 'leaveStats'] });
        },
        onError: (errors) => {
            console.error('Error updating leave status:', errors);
        }
    });
};
// Add flash message handling
const page = usePage()
const flash = computed(() => page.props.flash)
const applyFilters = () => {
    showFilterModal.value = false;
    // The filtering will happen automatically through your computed property
};
// Update handleEdit function
const handleEdit = (leave) => {
    selectedLeave.value = leave;
    showEditModal.value = true;
};
// Add new handleEditAction function
const handleEditAction = (action) => {
    actionType.value = action;
    showEditModal.value = false;
    showActionModal.value = true;
};
const confirmDelete = (leave) => {
    if (confirm('Are you sure you want to delete this leave request?')) {
        router.delete(route('leave-requests.destroy', { id: leave.id }), {
            preserveScroll: true,
            onSuccess: () => {
                // Optionally show success message
                router.reload({ only: ['leaves', 'leaveStats'] });
            },
            onError: (errors) => {
                console.error('Error deleting leave request:', errors);
            }
        });
    }
};
</script>
<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <!-- Add flash message display at the top of your content -->
        <div v-if="flash.message" 
             :class="{
                'bg-green-100 text-green-700': flash.success,
                'bg-red-100 text-red-700': flash.error
             }"
             class="p-4 mb-4 rounded-md">
            {{ flash.message }}
        </div>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Leave Management Dashboard
            </h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <!-- Pending Leaves Card -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ isAdmin ? 'Total Pending Leaves' : 'My Pending Leaves' }}
                                    </h3>
                                    <div class="mt-1 text-3xl font-semibold text-yellow-600">
                                        {{ leaveStats.pending }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Approved Leaves Card -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ isAdmin ? 'Total Approved Leaves' : 'My Approved Leaves' }}
                                    </h3>
                                    <div class="mt-1 text-3xl font-semibold text-green-600">
                                        {{ leaveStats.approved }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Rejected Leaves Card -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ isAdmin ? 'Total Rejected Leaves' : 'My Rejected Leaves' }}
                                    </h3>
                                    <div class="mt-1 text-3xl font-semibold text-red-600">
                                        {{ leaveStats.rejected }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Filter and Search Div -->
                <div class="mt-8 mb-4 flex justify-between items-center">
                    <!-- Search Section -->
                    <div class="max-w-xs">
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input
                                id="search"
                                v-model="searchQuery"
                                type="search"
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 pr-3 py-2 sm:text-sm border-gray-300 rounded-md"
                                placeholder="Search..."
                            >
                        </div>
                    </div>

                    <!-- Filter Button -->
                    <div class="relative">
                        <button 
                            @click="showFilterModal = !showFilterModal"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" 
                            class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filter
                        </button>
                        <!-- Filter Modal -->
                        <div v-if="showFilterModal" class="absolute z-10 mt-2 right-0 bg-white rounded-md shadow-lg p-4 w-96">
                            <div class="space-y-4">
                                <div>
                                    <label for="startDate" class="block text-sm font-medium text-gray-700">Start Date</label>
                                    <input
                                        type="date"
                                        id="startDate"
                                        v-model="startDateFilter"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                </div>
                                <div>
                                    <label for="endDate" class="block text-sm font-medium text-gray-700">End Date</label>
                                    <input
                                        type="date"
                                        id="endDate"
                                        v-model="endDateFilter"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <button
                                        @click="clearFilters"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Clear
                                    </button>
                                    <button
                                        @click="applyFilters"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Add a Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Leave Requests Table -->
                <div class="mt-8 overflow-x-auto">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th 
                                            @click="toggleSort('user.name')"
                                            class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                        >
                                            Employee
                                            <span v-if="sortColumn === 'user.name'" class="ml-1">
                                                {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                            </span>
                                        </th>
                                        <th 
                                            @click="toggleSort('start_date')"
                                            class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                        >
                                            Duration
                                            <span v-if="sortColumn === 'start_date'" class="ml-1">
                                                {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                            </span>
                                        </th>
                                        <th 
                                            @click="toggleSort('type')"
                                            class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                        >
                                            Type
                                            <span v-if="sortColumn === 'type'" class="ml-1">
                                                {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                            </span>
                                        </th>
                                        <th 
                                            @click="toggleSort('days')"
                                            class="px-6 py-3 text-center text-xs text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                        >
                                            No. of Days
                                            <span v-if="sortColumn === 'days'" class="ml-1">
                                                {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                            </span>
                                        </th>
                                        <th 
                                            @click="toggleSort('status')"
                                            class="px-6 py-3 text-center text-xs text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                        >
                                            Status
                                            <span v-if="sortColumn === 'status'" class="ml-1">
                                                {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                            </span>
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs text-gray-500 uppercase tracking-wider">
                                            Reason
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="leave in filteredLeaves" :key="leave.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ leave.user.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ formatDate(leave.start_date) }} to {{ formatDate(leave.end_date) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ getLeaveTypeLabel(leave.type) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="text-sm text-gray-900">{{ calculateDays(leave.start_date, leave.end_date) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <LeaveStatusBadge :status="leave.status" />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <InfoIcon :text="leave.reason" />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex justify-center space-x-2">
                                                <!-- Show Approve/Reject for pending leaves (admin only) -->
                                                <template v-if="isAdmin && leave.status === 'pending'">
                                                    <button
                                                        @click="handleAction(leave, 'approve')"
                                                        class="text-green-600 hover:text-green-900"
                                                    >
                                                        Approve
                                                    </button>
                                                    <button
                                                        @click="handleAction(leave, 'reject')"
                                                        class="text-red-600 hover:text-red-900"
                                                    >
                                                        Reject
                                                    </button>
                                                </template>
                                                <!-- Show Edit/Delete for approved or rejected leaves -->
                                                <template v-if="leave.status === 'approved' || leave.status === 'rejected'">
                                                    <button
                                                        @click="handleEdit(leave)"
                                                        class="text-blue-600 hover:text-blue-900"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        @click="confirmDelete(leave)"
                                                        class="text-red-600 hover:text-red-900"
                                                    >
                                                        Delete
                                                    </button>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Action Modal -->
                <transition name="modal">
                    <div v-if="showActionModal" class="fixed inset-0 z-50 overflow-y-auto">
                        <div class="flex items-center justify-center min-h-screen">
                            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    {{ actionType === 'approve' ? 'Approve Leave' : 'Reject Leave' }}
                                </h3>
                                <div class="mb-4">
                                    <label for="remarks" class="block text-sm font-medium text-gray-700">
                                        Admin Remarks
                                    </label>
                                    <textarea
                                        id="remarks"
                                        v-model="adminRemarks"
                                        class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-opacity-50"
                                        rows="3"
                                    ></textarea>
                                </div>
                                <div class="flex justify-end space-x-2">
                                    <button
                                        @click="submitAction"
                                        class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        Submit
                                    </button>
                                    <button
                                        @click="showActionModal = false"
                                        class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-transparent rounded-md shadow-sm hover:bg-gray-200 focus:outline-none"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
                <!-- Edit Modal -->
                <transition name="modal">
                    <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
                        <div class="flex items-center justify-center min-h-screen">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                            
                            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 relative">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    Edit Leave Status
                                </h3>
                                
                                <div class="flex justify-center space-x-4 mb-4">
                                    <button
                                        @click="handleEditAction('approve')"
                                        class="text-green-600 hover:text-green-900"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        @click="handleEditAction('reject')"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Reject
                                    </button>
                                </div>
                                <div class="flex justify-end">
                                    <button
                                        @click="showEditModal = false"
                                        class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-transparent rounded-md shadow-sm hover:bg-gray-200 focus:outline-none"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
