<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { api, ApiError } from '../api';
import { toast } from '../toast';
import { phases } from '../store';
import type { ActionItem, ApiResource, Retro, RetroBoard } from '../types';

const route = useRoute();
const board = ref<RetroBoard | null>(null);
const loading = ref(true);
const error = ref('');

const statusLabels: Record<ActionItem['status'], string> = {
    open: 'Открыто',
    in_progress: 'В работе',
    done: 'Сделано',
};

const statusClasses: Record<ActionItem['status'], string> = {
    open: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    done: 'bg-green-100 text-green-800',
};

const phaseFlow = computed(() => [...phases.value].sort((a, b) => a.index - b.index));

const phaseIndex = computed(() => phases.value.find((phase) => phase.value === board.value?.retro.phase)?.index ?? -1);

const columnDotClasses: Record<string, string> = {
    emerald: 'bg-emerald-500',
    rose: 'bg-rose-500',
    sky: 'bg-sky-500',
    amber: 'bg-amber-500',
};

const collecting = computed(() => board.value?.retro.phase === 'collect');
const voting = computed(() => board.value?.retro.phase === 'vote');
const inDiscussion = computed(() => ['discuss', 'done'].includes(board.value?.retro.phase ?? ''));

const myVotesCount = computed(() =>
    board.value?.columns
        .flatMap((column) => column.cards)
        .filter((card) => card.voted_by_me)
        .length ?? 0,
);

async function load() {
    try {
        board.value = await api.get<RetroBoard>(`/retros/${route.params.id}`);
        lastLoaded.value = Date.now();
    } catch (e) {
        error.value = e instanceof ApiError && e.status === 403 ? 'Нет доступа к этой доске' : 'Ошибка загрузки';
    } finally {
        loading.value = false;
    }
}

// --- индикатор свежести ---

const lastLoaded = ref(Date.now());
const now = ref(Date.now());

const updatedAgo = computed(() => {
    const seconds = Math.max(0, Math.floor((now.value - lastLoaded.value) / 1000));
    return seconds < 5 ? 'только что' : `${seconds} сек назад`;
});

async function advance() {
    const response = await api.post<ApiResource<Retro>>(`/retros/${route.params.id}/advance`);
    board.value!.retro = response.data;
    await load();
}

// --- карточки ---

const draftColumn = ref<number | null>(null);
const newCard = ref('');

async function addCard(columnId: number) {
    await api.post(`/columns/${columnId}/cards`, { body: newCard.value });
    newCard.value = '';
    draftColumn.value = null;
    await load();
}

async function deleteCard(cardId: number) {
    await api.delete(`/cards/${cardId}`);
    await load();
}

// --- голосование ---

async function toggleVote(cardId: number) {
    try {
        await api.post(`/cards/${cardId}/vote`);
    } catch (e) {
        if (e instanceof ApiError) {
            toast(e.message, 'error');
            return;
        }
    }
    await load();
}

// --- действия ---

const newItem = ref('');
const assigneeId = ref<number | ''>('');

async function addItem() {
    await api.post(`/retros/${route.params.id}/action-items`, {
        body: newItem.value,
        assignee_id: assigneeId.value === '' ? null : assigneeId.value,
    });
    newItem.value = '';
    assigneeId.value = '';
    await load();
}

async function setStatus(item: ActionItem, status: ActionItem['status']) {
    await api.patch(`/action-items/${item.id}`, { status });
    await load();
}

async function deleteItem(item: ActionItem) {
    await api.delete(`/action-items/${item.id}`);
    await load();
}

let timer: ReturnType<typeof setInterval>;
let clock: ReturnType<typeof setInterval>;
onMounted(() => {
    load();
    timer = setInterval(load, 5000);
    clock = setInterval(() => { now.value = Date.now(); }, 1000);
});
onBeforeUnmount(() => {
    clearInterval(timer);
    clearInterval(clock);
});
</script>

<template>
    <div v-if="loading" class="space-y-4 animate-pulse">
        <div class="h-24 bg-gray-200 rounded-card" />
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div v-for="n in 3" :key="n" class="space-y-3">
                <div class="h-6 w-1/2 bg-gray-200 rounded" />
                <div class="h-16 bg-gray-200 rounded-lg" />
                <div class="h-16 bg-gray-200 rounded-lg" />
            </div>
        </div>
    </div>
    <p v-else-if="error && !board" class="text-red-600">{{ error }}</p>
    <div v-else-if="board" class="space-y-4">
        <router-link :to="{ name: 'retros' }" class="btn-ghost w-fit">
            &larr; Назад к списку
        </router-link>

        <div class="card p-4 space-y-3">
            <ol class="flex items-center gap-2 flex-wrap">
                <template v-for="(phase, i) in phaseFlow" :key="phase.value">
                    <li class="flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full text-[11px] flex items-center justify-center font-medium transition-colors"
                              :class="i < phaseIndex
                                  ? 'bg-brand-600 text-white'
                                  : i === phaseIndex
                                      ? 'bg-brand-100 text-brand-700 ring-2 ring-brand-500'
                                      : 'bg-gray-200 text-ink-faint'">
                            {{ i + 1 }}
                        </span>
                        <span class="text-xs" :class="i === phaseIndex ? 'font-medium text-ink' : 'text-ink-faint'">
                            {{ phase.label }}
                        </span>
                    </li>
                    <li v-if="i < phaseFlow.length - 1" class="w-5 h-px bg-gray-300 shrink-0" aria-hidden="true" />
                </template>
            </ol>

            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-lg font-bold">{{ board.retro.title }}</h1>
                    <p class="text-sm text-ink-soft">
                        Участников: {{ board.retro.participants_count }}
                        · {{ board.retro.is_anonymous ? 'анонимный режим' : 'открытый режим' }}
                        · обновлено {{ updatedAgo }}
                    </p>
                    <p v-if="board.retro.invite_code" class="text-sm text-ink-soft">
                        Код приглашения: <span class="font-mono font-medium">{{ board.retro.invite_code }}</span>
                    </p>
                </div>
                <button v-if="board.retro.is_facilitator && board.retro.phase !== 'done'"
                        class="btn-primary" @click="advance">
                    Следующая фаза
                </button>
            </div>
        </div>

        <div v-if="voting" class="card px-4 py-3 flex items-center gap-3 text-sm w-fit">
            <span class="text-ink-soft">Ваши голоса:</span>
            <span class="font-semibold">{{ myVotesCount }} / {{ board.retro.votes_per_user }}</span>
            <span class="w-32 h-2 rounded-full bg-surface-muted overflow-hidden">
                <span class="block h-full bg-brand-500 transition-all duration-300"
                      :style="{ width: `${(myVotesCount / board.retro.votes_per_user) * 100}%` }" />
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
            <div v-for="column in board.columns" :key="column.id" class="card p-4">
                <h3 class="font-medium flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full" :class="columnDotClasses[column.color] ?? 'bg-gray-300'" />
                    {{ column.title }}
                </h3>
                <ul class="mt-3 space-y-2 board-cards">
                    <li v-for="card in column.cards" :key="card.id"
                        class="bg-surface-muted/70 border border-gray-200 rounded-lg p-3 text-sm flex items-start justify-between gap-2 transition hover:shadow-card-hover">
                        <span class="whitespace-pre-wrap">{{ card.body }}</span>
                        <span class="flex items-center gap-2 shrink-0 text-xs text-ink-faint">
                            <template v-if="card.author_name">{{ card.author_name }}</template>
                            <button v-if="voting" type="button" @click="toggleVote(card.id)"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border transition"
                                    :class="card.voted_by_me
                                        ? 'bg-brand-100 border-brand-300 text-brand-700'
                                        : 'border-gray-300 text-ink-soft hover:border-brand-300 hover:text-brand-600'">
                                ▲ {{ card.votes_count }}
                            </button>
                            <span v-else-if="card.votes_count > 0" class="px-2 py-0.5 rounded-full bg-gray-100 text-ink-soft">▲ {{ card.votes_count }}</span>
                            <button v-if="(card.is_mine || board.retro.is_facilitator) && collecting"
                                    class="text-gray-300 hover:text-red-500 font-bold transition" @click="deleteCard(card.id)">&times;</button>
                        </span>
                    </li>
                </ul>
                <p v-if="!column.cards.length" class="text-sm text-ink-faint mt-3">Пока пусто</p>

                <form v-if="collecting && draftColumn === column.id" class="mt-3 space-y-2" @submit.prevent="addCard(column.id)">
                    <textarea v-model="newCard" rows="3" required class="input"
                              placeholder="Напишите карточку..."></textarea>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary">Добавить</button>
                        <button type="button" class="text-sm text-ink-soft hover:text-ink" @click="draftColumn = null">Отмена</button>
                    </div>
                </form>
                <button v-else-if="collecting" type="button"
                        class="mt-3 w-full border border-dashed border-gray-300 rounded-lg py-2 text-sm text-ink-soft hover:border-brand-400 hover:text-brand-600 transition"
                        @click="draftColumn = column.id">
                    + Добавить карточку
                </button>
            </div>
        </div>

        <section v-if="inDiscussion" class="card p-4">
            <h3 class="font-semibold mb-4">Действия</h3>
            <form class="flex items-start gap-4 mb-4" @submit.prevent="addItem">
                <input v-model="newItem" required placeholder="Новое действие..." class="input flex-1" />
                <select v-model="assigneeId" class="input w-56">
                    <option value="">Без ответственного</option>
                    <option v-for="participant in board.participants" :key="participant.id" :value="participant.id">
                        {{ participant.name }}
                    </option>
                </select>
                <button type="submit" class="btn-primary">Добавить</button>
            </form>
            <p v-if="!board.action_items.length" class="text-sm text-ink-faint">Пока нет действий.</p>
            <ul v-else class="space-y-2">
                <li v-for="item in board.action_items" :key="item.id"
                    class="flex items-center gap-3 border border-gray-200 rounded-lg p-3 text-sm bg-surface">
                    <select :value="item.status" class="text-xs font-medium border-0 rounded-full px-2 py-1" :class="statusClasses[item.status]"
                            @change="setStatus(item, ($event.target as HTMLSelectElement).value as ActionItem['status'])">
                        <option v-for="(label, status) in statusLabels" :key="status" :value="status">{{ label }}</option>
                    </select>
                    <span class="flex-1">{{ item.body }}</span>
                    <span v-if="item.assignee" class="text-xs text-ink-soft">{{ item.assignee.name }}</span>
                    <button v-if="board.retro.is_facilitator" class="text-gray-300 hover:text-red-500 font-bold transition" @click="deleteItem(item)">&times;</button>
                </li>
            </ul>
        </section>
    </div>
</template>

<style scoped>
@keyframes card-in {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.board-cards li {
    animation: card-in 0.2s ease-out;
}
</style>
