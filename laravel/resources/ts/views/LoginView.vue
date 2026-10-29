<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { api, ApiError } from '../api';
import { login } from '../store';
import AuthCard from '../components/AuthCard.vue';
import type { User } from '../types';

const router = useRouter();
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        const data = await api.post<{ token: string; user: User }>('/login', {
            email: email.value,
            password: password.value,
        });
        login(data.token, data.user.name);
        router.push({ name: 'retros' });
    } catch (e) {
        error.value = e instanceof ApiError ? 'Неверные учётные данные' : 'Ошибка сети';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AuthCard>
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="block text-sm text-ink-soft mb-1">Email</label>
                <input v-model="email" type="email" required class="input" />
            </div>
            <div>
                <label class="block text-sm text-ink-soft mb-1">Пароль</label>
                <input v-model="password" type="password" required class="input" />
            </div>
            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
            <button type="submit" :disabled="loading" class="btn-primary w-full">
                {{ loading ? 'Входим...' : 'Войти' }}
            </button>
            <div class="flex justify-between text-sm text-ink-soft">
                <router-link :to="{ name: 'forgot-password' }" class="text-brand-600 hover:underline">Забыли пароль?</router-link>
                <span>
                    Нет аккаунта?
                    <router-link :to="{ name: 'register' }" class="text-brand-600 hover:underline">Регистрация</router-link>
                </span>
            </div>
        </form>
    </AuthCard>
</template>
