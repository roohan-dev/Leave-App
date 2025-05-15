<script setup>
defineProps({
    message: {
        type: String,
        required: true
    }
});
</script>

<template>
    <p class="mt-2 text-sm text-red-600" role="alert">{{ message }}</p>
</template>