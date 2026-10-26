<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { api, ApiError } from '../api';
import { login } from '../store';
import AuthCard from '../components/AuthCard.vue';
import type { User } from '../types';

const router = useRouter();
const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const errors = ref<Record<string, string[]>>({});
const loading = ref(false);

async function submit() {
    errors.value = {};
    loading.value = true;
    try {
        const data = await api.post<{ token: string; user: User }>('/register', {
            name: name.value,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        login(data.token, data.user.name);
        router.push({ name: 'retros' });
    } catch (e) {
        errors.value = e instanceof ApiError ? e.errors : {};
        if (!(e instanceof ApiError)) {
            errors.value = { email: ['Ошибка сети'] };
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AuthCard>
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="block text-sm text-ink-soft mb-1">Имя</label>
                <input v-model="name" type="text" required class="input" />
                <p v-if="errors.name" class="text-xs text-red-600 mt-1">{{ errors.name[0] }}</p>
            </div>
            <div>
                <label class="block text-sm text-ink-soft mb-1">Email</label>
                <input v-model="email" type="email" required class="input" />
                <p v-if="errors.email" class="text-xs text-red-600 mt-1">{{ errors.email[0] }}</p>
            </div>
            <div>
                <label class="block text-sm text-ink-soft mb-1">Пароль</label>
                <input v-model="password" type="password" required class="input" />
                <p v-if="errors.password" class="text-xs text-red-600 mt-1">{{ errors.password[0] }}</p>
            </div>
            <div>
                <label class="block text-sm text-ink-soft mb-1">Повторите пароль</label>
                <input v-model="passwordConfirmation" type="password" required class="input" />
            </div>
            <button type="submit" :disabled="loading" class="btn-primary w-full">
                {{ loading ? 'Создаём...' : 'Зарегистрироваться' }}
            </button>
        </form>
    </AuthCard>
</template>
