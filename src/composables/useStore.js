import { reactive, provide, inject } from 'vue';

const STORE_KEY = Symbol('b418TgCf7Store');

/**
 * Central reactive state shared by every panel. Created once in Admin.vue
 * and injected everywhere else. Panels mutate collections with the fresh
 * arrays returned by the AJAX endpoints, so state never drifts from the DB.
 */
export function provideStore() {
    const store = reactive({
        loading: true,
        loadError: '',
        cf7Active: false,
        forms: [],
        bots: [],
        chats: [],
        templates: [],
        rules: [],
        log: [],
        defaultTemplate: '',
        specialTags: {},
    });

    provide(STORE_KEY, store);

    return store;
}

export function useStore() {
    return inject(STORE_KEY);
}

/* ── Lookup helpers ──────────────────────────────────────────── */

export function botById(store, id) {
    return store.bots.find((b) => b.id === id) || null;
}

export function chatById(store, id) {
    return store.chats.find((c) => c.id === id) || null;
}

export function templateById(store, id) {
    return store.templates.find((t) => t.id === id) || null;
}

export function formById(store, id) {
    return store.forms.find((f) => Number(f.id) === Number(id)) || null;
}

export function formTitle(store, id) {
    if (!Number(id)) {
        return 'All forms';
    }
    const form = formById(store, id);
    return form ? form.title : `Form #${id} (deleted)`;
}
