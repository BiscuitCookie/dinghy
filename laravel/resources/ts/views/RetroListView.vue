<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { api, ApiError } from '../api';
import { phaseIndex, phaseLabel, templates } from '../store';
import type { ApiResource, Retro } from '../types';

const router = useRouter();
const owned = ref<Retro[]>([]);
const joined = ref<Retro[]>([]);
const loading = ref(true);
const inviteCode = ref('');
const joinError = ref('');
const newTitle = ref('');
const newTemplate = ref('classic');
const query = ref('');
const sortBy = ref<'active' | 'title' | 'date'>('active');

const visibleRetros = computed(() => {
    const all = [...owned.value, ...joined.value];
    const filtered = query.value.trim()
        ? all.filter((retro) => retro.title.toLowerCase().includes(query.value.trim().toLowerCase()))
        : all;

    return [...filtered].sort((a, b) => {
        if (sortBy.value === 'title') {
            return a.title.localeCompare(b.title, 'ru');
        }
        if (sortBy.value === 'date') {
            return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
        }
        return phaseIndex(a.phase) - phaseIndex(b.phase)
            || new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
    });
});

const phaseClasses: Record<Retro['phase'], string> = {
    draft: 'bg-phase-draft',
    collect: 'bg-phase-collect',
    vote: 'bg-phase-vote',
    discuss: 'bg-phase-discuss',
    done: 'bg-phase-done',
};

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' });
}

async function load() {
    const data = await api.get<{ owned: Retro[]; joined: Retro[] }>('/retros');
    owned.value = data.owned;
    joined.value = data.joined;
    loading.value = false;
}

async function join() {
    joinError.value = '';
    try {
        const response = await api.post<ApiResource<Retro>>('/retros/join', { invite_code: inviteCode.value.trim().toUpperCase() });
        router.push({ name: 'retro-show', params: { id: response.data.id } });
    } catch (e) {
        joinError.value = e instanceof ApiError ? 'Код не найден' : 'Ошибка сети';
    }
}

async function createRetro() {
    if (!newTitle.value.trim()) return;
    const response = await api.post<ApiResource<Retro>>('/retros', {
        title: newTitle.value,
        template: newTemplate.value,
        is_anonymous: true,
        votes_per_user: 3,
    });
    router.push({ name: 'retro-show', params: { id: response.data.id } });
}

function open(retro: Retro) {
    router.push({ name: 'retro-show', params: { id: retro.id } });
}

onMounted(load);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-bold">Ретроспективы</h1>
            <form class="flex flex-wrap gap-2 w-full sm:w-auto" @submit.prevent="createRetro">
                <input v-model="newTitle" placeholder="Название: Ретро спринта 43"
                       class="input sm:w-56" />
                <select v-model="newTemplate" class="input sm:w-64">
                    <option v-for="template in templates" :key="template.key" :value="template.key">
                        {{ template.label }}
                    </option>
                </select>
                <button type="submit" class="btn-primary whitespace-nowrap" :disabled="!newTitle.trim()">
                    + Новое ретро
                </button>
            </form>
        </div>

        <form class="card px-4 py-3 flex items-center gap-3" @submit.prevent="join">
            <span class="text-sm text-ink-soft shrink-0">Есть код приглашения?</span>
            <input v-model="inviteCode" maxlength="8" placeholder="ABCD1234"
                   class="input w-40 uppercase font-mono" />
            <button type="submit" class="btn-ghost">Присоединиться</button>
            <p v-if="joinError" class="text-sm text-red-600">{{ joinError }}</p>
        </form>

        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 animate-pulse">
            <div v-for="n in 3" :key="n" class="h-28 bg-gray-200 rounded-card" />
        </div>

        <template v-else>
            <div class="flex flex-wrap items-center gap-3">
                <input v-model="query" placeholder="Поиск по названию..."
                       class="input sm:w-64" />
                <select v-model="sortBy" class="input w-52">
                    <option value="active">Сначала активные</option>
                    <option value="date">Сначала новые</option>
                    <option value="title">По названию</option>
                </select>
            </div>

            <div v-if="visibleRetros.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <button v-for="retro in visibleRetros" :key="retro.id" type="button"
                        class="card text-left p-5 relative overflow-hidden transition hover:shadow-card-hover hover:-translate-y-0.5"
                        @click="open(retro)">
                    <span class="absolute inset-x-0 top-0 h-1" :class="phaseClasses[retro.phase]" />
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold leading-snug">{{ retro.title }}</h3>
                        <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium text-white" :class="phaseClasses[retro.phase]">
                            {{ phaseLabel(retro.phase) }}
                        </span>
                    </div>
                    <p class="mt-3 flex items-center gap-3 text-xs text-ink-faint">
                        <span>{{ retro.participants_count }} участников</span>
                        <span>·</span>
                        <span>{{ retro.cards_count }} карточек</span>
                        <span>·</span>
                        <span>{{ formatDate(retro.created_at) }}</span>
                    </p>
                </button>
            </div>

            <div v-else class="card p-10 text-center">
                <template v-if="owned.length || joined.length">
                    <p class="text-ink-soft">Ничего не найдено.</p>
                    <p class="text-sm text-ink-faint mt-1">Попробуйте изменить запрос или сортировку.</p>
                </template>
                <template v-else>
                    <p class="text-ink-soft">Пока пусто.</p>
                    <p class="text-sm text-ink-faint mt-1">Создайте ретро кнопкой выше или вступите по коду приглашения.</p>
                </template>
            </div>
        </template>
    </div>
</template>
