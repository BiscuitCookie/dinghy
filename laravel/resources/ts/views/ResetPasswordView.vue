<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api, ApiError } from '../api';
import AuthCard from '../components/AuthCard.vue';

const route = useRoute();
const router = useRouter();

const email = ref(typeof route.query.email === 'string' ? route.query.email : '');
const password = ref('');
const passwordConfirmation = ref('');
const errors = ref<Record<string, string[]>>({});
const loading = ref(false);

async function submit() {
    errors.value = {};
    loading.value = true;
    try {
        await api.post('/reset-password', {
            email: email.value,
            token: route.params.token,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        router.push({ name: 'login' });
    } catch (e) {
        if (e instanceof ApiError) {
            errors.value = e.errors;
            if (!Object.keys(e.errors).length) {
                errors.value = { password: [e.message] };
            }
        } else {
            errors.value = { password: ['Ошибка сети'] };
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
                <label class="block text-sm text-ink-soft mb-1">Email</label>
                <input v-model="email" type="email" required class="input" />
            </div>
            <div>
                <label class="block text-sm text-ink-soft mb-1">Новый пароль</label>
                <input v-model="password" type="password" required class="input" />
                <p v-if="errors.password" class="text-xs text-red-600 mt-1">{{ errors.password[0] }}</p>
            </div>
            <div>
                <label class="block text-sm text-ink-soft mb-1">Повторите пароль</label>
                <input v-model="passwordConfirmation" type="password" required class="input" />
            </div>
            <p v-if="errors.token" class="text-sm text-red-600">{{ errors.token[0] }}</p>
            <button type="submit" :disabled="loading" class="btn-primary w-full">
                {{ loading ? 'Сохраняем...' : 'Сбросить пароль' }}
            </button>
        </form>
    </AuthCard>
</template>
