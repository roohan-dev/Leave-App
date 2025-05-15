<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import FormError from '@/Components/FormError.vue';

const props = defineProps({
    show: Boolean,
    leave: Object,
    action: {
        type: String,
        validator: (value) => ['approve', 'reject'].includes(value)
    }
});

const emit = defineEmits(['update:show']);

const form = useForm({
    status: props.action,
    admin_remarks: ''
});

const submit = () => {
    form.put(route('leave-requests.update-status', props.leave?.id), {
        onSuccess: () => {
            emit('update:show', false);
            form.reset();
        }
    });
};
</script>

<template>
    <Modal :show="show" @close="$emit('update:show', false)">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ action === 'approve' ? 'Approve' : 'Reject' }} Leave Request
            </h2>

            <form @submit.prevent="submit" class="mt-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Admin Remarks
                    </label>
                    <textarea
                        v-model="form.admin_remarks"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                        required
                    ></textarea>
                    <FormError 
                        v-if="form.errors.admin_remarks" 
                        :message="form.errors.admin_remarks" 
                    />
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        @click="$emit('update:show', false)"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :class="{
                            'px-4 py-2 text-sm font-medium text-white rounded-md': true,
                            'bg-green-600 hover:bg-green-700': action === 'approve',
                            'bg-red-600 hover:bg-red-700': action === 'reject'
                        }"
                        :disabled="form.processing"
                    >
                        {{ action === 'approve' ? 'Approve' : 'Reject' }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>