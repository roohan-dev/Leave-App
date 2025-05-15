<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormError from '@/Components/FormError.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    leaveTypes: {
        type: Object,
        default: () => ({
            sick: 'Sick Leave',
            vacation: 'Vacation Leave',
            maternity: 'Maternity Leave',
            personal: 'Personal Leave',
            emergency: 'Emergency Leave',
            marriage: 'Marriage Leave'
        })
    }
});

const message = ref('');
const messageType = ref(''); // 'success' or 'error'

const form = useForm({
    start_date: '',
    end_date: '',
    reason: '',
    type: ''
});

const submit = () => {
    form.post(route('leave-requests.store'), {
        preserveScroll: true,
        onSuccess: (response) => {
            // Reset form
            form.reset();
            
            // Show success message
            message.value = response?.props?.message || 'Leave request submitted successfully.';
            messageType.value = 'success';
            
            // Redirect to dashboard after short delay
            setTimeout(() => {
                router.visit(route('dashboard'));
            }, 1500);
        },
        onError: (errors) => {
            message.value = errors?.message || 'Failed to submit leave request. Please try again.';
            messageType.value = 'error';
            
            // Clear error message after 5 seconds
            setTimeout(() => {
                message.value = '';
            }, 5000);
        }
    });
};
</script>

<template>
    <Head title="Leave Requests" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">New Leave Request</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Alert Message -->
                        <div v-if="message" 
                             :class="[
                                'mb-4 p-4 rounded-md',
                                messageType === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
                             ]">
                            {{ message }}
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Leave Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Leave Type
                                </label>
                                <select
                                    v-model="form.type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    required
                                >
                                    <option value="">Select Type</option>
                                    <option
                                        v-for="(label, value) in leaveTypes"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{ label }}
                                    </option>
                                </select>
                                <FormError v-if="form.errors.type" :message="form.errors.type" />
                            </div>

                            <!-- Start Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Start Date
                                </label>
                                <input
                                    v-model="form.start_date"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :min="new Date().toISOString().split('T')[0]"
                                    required
                                />
                                <FormError v-if="form.errors.start_date" :message="form.errors.start_date" />
                            </div>

                            <!-- End Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    End Date
                                </label>
                                <input
                                    v-model="form.end_date"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :min="form.start_date || new Date().toISOString().split('T')[0]"
                                    required
                                />
                                <FormError v-if="form.errors.end_date" :message="form.errors.end_date" />
                            </div>

                            <!-- Reason -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Reason
                                </label>
                                <textarea
                                    v-model="form.reason"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    required
                                ></textarea>
                                <FormError v-if="form.errors.reason" :message="form.errors.reason" />
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end space-x-4">
                                <Link
                                    :href="route('dashboard')"
                                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    class="px-4 py-2 text-white bg-blue-500 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>