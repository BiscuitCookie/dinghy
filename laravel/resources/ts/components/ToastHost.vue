<script setup lang="ts">
import { dismiss, toasts } from '../toast';
</script>

<template>
    <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 w-72">
        <div v-for="item in toasts" :key="item.id"
             class="rounded-lg px-4 py-3 text-sm shadow-card-hover flex items-start justify-between gap-3 toast-in"
             :class="item.type === 'error' ? 'bg-red-50 text-red-800 border border-red-200' : 'bg-surface text-ink border border-gray-200'">
            <span>{{ item.message }}</span>
            <button class="text-ink-faint hover:text-ink shrink-0" @click="dismiss(item.id)">&times;</button>
        </div>
    </div>
</template>

<style scoped>
@keyframes toast-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.toast-in {
    animation: toast-in 0.2s ease-out;
}
</style>
