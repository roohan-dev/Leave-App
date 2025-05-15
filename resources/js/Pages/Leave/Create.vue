<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormError from '@/Components/FormError.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    leaveTypes: {
        type: Object,
        default: () => ({
            sick: 'Sick Leave',
            vacation: 'Vacation',
            personal: 'Personal Leave'
        })
    }
});

const form = useForm({
    start_date: '',
    end_date: '',
    reason: '',
    type: '',
    documents: null
});

const submit = () => {
    form.post(route('leave-requests.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        }
    });
};
</script>

<template>
    <Head title="New Leave Request" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                New Leave Request
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
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

                            <!-- Supporting Documents -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Supporting Documents (Optional)
                                </label>
                                <input
                                    type="file"
                                    @input="form.documents = $event.target.files[0]"
                                    class="mt-1 block w-full"
                                    accept=".pdf,.doc,.docx"
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    PDF, DOC, DOCX up to 2MB
                                </p>
                                <FormError v-if="form.errors.documents" :message="form.errors.documents" />
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end space-x-4">
                                <button
                                    type="button"
                                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                                    @click="$inertia.visit(route('leave-requests.index'))"
                                >
                                    Cancel
                                </button>
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