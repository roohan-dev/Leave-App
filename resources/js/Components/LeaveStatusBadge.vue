<script setup>
defineProps({
    status: {
        type: String,
        required: true,
        validator: (value) => ['pending', 'approved', 'rejected'].includes(value)
    }
});

const statusConfig = {
    pending: {
        class: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
        text: 'Pending',
        icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
    },
    approved: {
        class: 'bg-green-100 text-green-800 border border-green-200',
        text: 'Approved',
        icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    },
    rejected: {
        class: 'bg-red-100 text-red-800 border border-red-200',
        text: 'Rejected',
        icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
    }
};
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium shadow-sm"
        :class="statusConfig[status].class"
    >
        <svg 
            class="w-3.5 h-3.5" 
            fill="none" 
            viewBox="0 0 24 24" 
            stroke="currentColor"
        >
            <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                :d="statusConfig[status].icon"
            />
        </svg>
        {{ statusConfig[status].text }}
    </span>
</template>