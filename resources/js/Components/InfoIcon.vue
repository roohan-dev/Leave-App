<script setup>
import { ref } from 'vue';

defineProps({
  text: {
    type: String,
    required: true
  }
});

const showTooltip = ref(false);
</script>

<template>
  <div class="relative inline-block">
    <svg 
      @mouseenter="showTooltip = true"
      @mouseleave="showTooltip = false"
      xmlns="http://www.w3.org/2000/svg" 
      class="h-5 w-5 text-gray-500 hover:text-gray-700 cursor-pointer" 
      fill="none" 
      viewBox="0 0 24 24" 
      stroke="currentColor"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <div 
      v-if="showTooltip"
      class="absolute z-10 w-64 px-4 py-2 text-sm text-gray-500 bg-white border rounded shadow-lg"
      style="bottom: 100%; left: 50%; transform: translateX(-50%)"
    >
      {{ text }}
    </div>
  </div>
</template>