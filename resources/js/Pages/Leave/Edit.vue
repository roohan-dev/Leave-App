Roohan Naeem\Desktop\WEB WORK\All Projects\new_app\resources\js\Pages\Leave\Edit.vue
<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LeaveForm from '@/Components/LeaveForm.vue';

defineProps({
    leave: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <Head title="Edit Leave Request" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Leave Request
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <LeaveForm 
                            mode="edit"
                            :leave="leave"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>