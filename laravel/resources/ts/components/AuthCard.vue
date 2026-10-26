<script setup lang="ts">
import { useRoute } from 'vue-router';
import AppLogo from './AppLogo.vue';

const route = useRoute();

const tabs = [
    { name: 'login', label: 'Вход' },
    { name: 'register', label: 'Регистрация' },
] as const;
</script>

<template>
    <div class="min-h-[85vh] flex items-center justify-center py-8">
        <div class="w-full max-w-md">
            <div class="text-center mb-6">
                <router-link :to="{ name: 'login' }" class="inline-flex flex-col items-center gap-2">
                    <AppLogo :size="56" />
                    <span class="text-2xl font-bold tracking-tight">Dinghy <span class="text-brand-600">— Ретро</span></span>
                </router-link>
                <p class="mt-1 text-sm text-ink-soft">Проводите ретроспективы без хаоса</p>
            </div>

            <div class="card p-6 sm:p-8">
                <nav class="flex gap-1 bg-surface-muted rounded-lg p-1 mb-6">
                    <router-link v-for="tab in tabs" :key="tab.name"
                                 :to="{ name: tab.name }"
                                 class="flex-1 text-center py-2 rounded-md text-sm font-medium transition"
                                 :class="route.name === tab.name
                                     ? 'bg-surface shadow-card text-ink'
                                     : 'text-ink-soft hover:text-ink'">
                        {{ tab.label }}
                    </router-link>
                </nav>

                <slot />
            </div>
        </div>
    </div>
</template>
