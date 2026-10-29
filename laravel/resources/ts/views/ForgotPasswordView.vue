<script setup lang="ts">
import { ref } from 'vue';
import { api, ApiError } from '../api';
import AuthCard from '../components/AuthCard.vue';

const email = ref('');
const sent = ref(false);
const error = ref('');
const loading = ref(false);

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        await api.post('/forgot-password', { email: email.value });
        sent.value = true;
    } catch (e) {
        error.value = e instanceof ApiError ? 'Проверьте корректность email' : 'Ошибка сети';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AuthCard>
        <template v-if="sent">
            <p class="text-sm text-ink text-center py-2">
                Если аккаунт с таким email существует, письмо со ссылкой для сброса пароля отправлено.
            </p>
            <p class="text-sm text-center mt-4">
                <router-link :to="{ name: 'login' }" class="text-brand-600 hover:underline">Вернуться ко входу</router-link>
            </p>
        </template>
        <form v-else class="space-y-4" @submit.prevent="submit">
            <p class="text-sm text-ink-soft">Укажите email аккаунта — отправим ссылку для сброса пароля.</p>
            <div>
                <label class="block text-sm text-ink-soft mb-1">Email</label>
                <input v-model="email" type="email" required class="input" />
            </div>
            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
            <button type="submit" :disabled="loading" class="btn-primary w-full">
                {{ loading ? 'Отправляем...' : 'Отправить ссылку' }}
            </button>
            <p class="text-sm text-ink-soft text-center">
                Вспомнили пароль?
                <router-link :to="{ name: 'login' }" class="text-brand-600 hover:underline">Войти</router-link>
            </p>
        </form>
    </AuthCard>
</template>
