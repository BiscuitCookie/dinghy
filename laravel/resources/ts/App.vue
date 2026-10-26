<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppLogo from './components/AppLogo.vue';
import ToastHost from './components/ToastHost.vue';
import { ensureMeta, isAuthed, logout, userName } from './store';

const route = useRoute();
const router = useRouter();

const isAuthPage = computed(() => ['login', 'register'].includes(route.name as string));

function onLogout() {
    logout();
    router.push({ name: 'login' });
}

onMounted(() => {
    if (isAuthed.value) {
        void ensureMeta();
    }
});
</script>

<template>
    <div class="min-h-screen transition-colors"
         :class="isAuthPage ? 'bg-gradient-to-br from-brand-50 via-surface-muted to-amber-100/60' : 'bg-surface-muted'">
        <header v-if="isAuthed" class="bg-surface/90 backdrop-blur border-b border-gray-200 sticky top-0 z-10">
            <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
                <router-link :to="{ name: 'retros' }" class="flex items-center gap-2.5">
                    <AppLogo :size="30" />
                    <span class="text-lg font-bold">Dinghy — Ретро</span>
                </router-link>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-ink-soft">{{ userName }}</span>
                    <button class="text-sm text-ink-soft hover:text-ink transition" @click="onLogout">Выйти</button>
                </div>
            </div>
        </header>
        <main class="max-w-6xl mx-auto px-4 py-6">
            <router-view />
        </main>
        <ToastHost />
    </div>
</template>
