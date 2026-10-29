import { createRouter, createWebHistory } from 'vue-router';
import { getToken } from './api';

const router = createRouter({
    history: createWebHistory('/app'),
    routes: [
        { path: '/login', name: 'login', component: () => import('./views/LoginView.vue') },
        { path: '/register', name: 'register', component: () => import('./views/RegisterView.vue') },
        { path: '/forgot-password', name: 'forgot-password', component: () => import('./views/ForgotPasswordView.vue') },
        { path: '/reset-password/:token', name: 'reset-password', component: () => import('./views/ResetPasswordView.vue') },
        { path: '/', name: 'retros', component: () => import('./views/RetroListView.vue'), meta: { auth: true } },
        { path: '/retros/:id', name: 'retro-show', component: () => import('./views/RetroShowView.vue'), meta: { auth: true } },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
});

router.beforeEach((to) => {
    if (to.meta.auth && !getToken()) {
        return { name: 'login' };
    }
    if (to.name === 'login' && getToken()) {
        return { name: 'retros' };
    }
});

export default router;
