<script setup>
import { useForm } from '@inertiajs/vue3';
import FormError from '@/Components/FormError.vue';

const props = defineProps({
    mode: {
        type: String,
        default: 'create',
        validator: value => ['create', 'edit'].includes(value)
    },
    leave: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    start_date: props.leave.start_date || '',
    end_date: props.leave.end_date || '',
    reason: props.leave.reason || ''
});

const submit = () => {
    if (props.mode === 'edit') {
        form.put(route('leave-requests.update', props.leave.id));
    } else {
        form.post(route('leave-requests.store'));
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Start Date
            </label>
            <input
                v-model="form.start_date"
                type="date"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                required
            />
            <FormError v-if="form.errors.start_date" :message="form.errors.start_date" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                End Date
            </label>
            <input
                v-model="form.end_date"
                type="date"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                required
            />
            <FormError v-if="form.errors.end_date" :message="form.errors.end_date" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Reason
            </label>
            <textarea
                v-model="form.reason"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                required
            ></textarea>
            <FormError v-if="form.errors.reason" :message="form.errors.reason" />
        </div>

        <div class="flex justify-end space-x-3">
            <a
                :href="route('leave-requests.index')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
                Cancel
            </a>
            <button
                type="submit"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                :disabled="form.processing"
            >
                {{ mode === 'edit' ? 'Update' : 'Create' }} Request
            </button>
        </div>
    </form>
</template>