import { computed, ref } from 'vue';
import { api, getToken, setToken } from './api';

const token = ref<string | null>(getToken());

export const isAuthed = computed(() => token.value !== null);
export const userName = ref(localStorage.getItem('retro_user_name') ?? '');

export function login(tokenValue: string, name: string): void {
    token.value = tokenValue;
    setToken(tokenValue);
    userName.value = name;
    localStorage.setItem('retro_user_name', name);
    void ensureMeta();
}

export function logout(): void {
    token.value = null;
    setToken(null);
    userName.value = '';
    localStorage.removeItem('retro_user_name');
}

// --- метаданные из API (фазы, шаблоны) ---

export interface PhaseMeta {
    value: string;
    label: string;
    index: number;
}

export interface TemplateMeta {
    key: string;
    label: string;
}

export const phases = ref<PhaseMeta[]>([]);
export const templates = ref<TemplateMeta[]>([]);

let metaLoaded = false;

export async function ensureMeta(): Promise<void> {
    if (metaLoaded || !isAuthed.value) {
        return;
    }
    const data = await api.get<{ phases: PhaseMeta[]; templates: TemplateMeta[] }>('/meta');
    phases.value = data.phases;
    templates.value = data.templates;
    metaLoaded = true;
}

export function phaseLabel(value: string): string {
    return phases.value.find((phase) => phase.value === value)?.label ?? value;
}

export function phaseIndex(value: string): number {
    return phases.value.find((phase) => phase.value === value)?.index ?? 99;
}
