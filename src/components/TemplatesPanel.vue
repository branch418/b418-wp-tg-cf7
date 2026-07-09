<template>
    <Card
        title="Message Templates"
        description="Shape the Telegram message for each form. Use [field-name] tags for form fields — the same names as in your CF7 mail settings."
    >
        <template #actions>
            <button type="button" class="b418-btn b418-btn--primary b418-btn--sm" @click="openModal()">
                + Add template
            </button>
        </template>

        <Alert v-if="notice" :variant="notice.variant" dismissible @dismiss="dismiss">
            {{ notice.text }}
        </Alert>

        <div class="tg-list">
            <div class="tg-item">
                <div class="tg-item__main">
                    <div class="tg-item__title">
                        Default template
                        <Badge variant="accent" size="sm">built-in</Badge>
                    </div>
                    <div class="tg-item__meta">
                        Used by connections without a template — sends every submitted field.
                    </div>
                </div>
                <div class="tg-item__actions">
                    <button type="button" class="b418-btn b418-btn--ghost b418-btn--sm" @click="showDefault = !showDefault">
                        {{ showDefault ? 'Hide' : 'Preview' }}
                    </button>
                </div>
            </div>
            <pre v-if="showDefault" class="tg-pre">{{ store.defaultTemplate }}</pre>

            <div v-for="tpl in store.templates" :key="tpl.id" class="tg-item">
                <div class="tg-item__main">
                    <div class="tg-item__title">
                        {{ tpl.name }}
                        <Badge :variant="tpl.form_id ? 'info' : 'neutral'" size="sm">{{ formTitle(store, tpl.form_id) }}</Badge>
                        <Badge v-if="tpl.parse_mode === 'HTML'" variant="success" size="sm">HTML</Badge>
                    </div>
                    <div class="tg-item__meta">
                        <span>{{ excerpt(tpl.body) }}</span>
                    </div>
                </div>
                <div class="tg-item__actions">
                    <button type="button" class="tg-icon-btn" title="Edit" @click="openModal(tpl)">
                        <svg width="15" height="15" viewBox="0 0 20 20" fill="none"><path d="M13.5 3.5l3 3L7 16H4v-3l9.5-9.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                    </button>
                    <button type="button" class="tg-icon-btn tg-icon-btn--danger" title="Delete" @click="askDelete(tpl)">
                        <svg width="15" height="15" viewBox="0 0 20 20" fill="none"><path d="M4 6h12M8 6V4h4v2m-6.5 0l.6 10h7.8l.6-10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </Card>

    <Modal v-model="modalOpen" :title="form.id ? 'Edit template' : 'Add template'" size="lg">
        <div class="tg-form">
            <Alert v-if="modalError" variant="danger">{{ modalError }}</Alert>

            <div class="tg-row">
                <FieldGroup label="Name" required>
                    <input v-model.trim="form.name" type="text" class="tg-input" placeholder="e.g. Contact page notification" />
                </FieldGroup>
                <FieldGroup label="For form" hint="Choosing a form unlocks its field tags below.">
                    <select v-model="form.form_id" class="tg-select">
                        <option :value="0">Any form</option>
                        <option v-for="f in store.forms" :key="f.id" :value="f.id">{{ f.title }}</option>
                    </select>
                </FieldGroup>
                <FieldGroup label="Formatting" hint="HTML allows <b>, <i>, <a href>, <code>…">
                    <select v-model="form.parse_mode" class="tg-select">
                        <option value="none">Plain text</option>
                        <option value="HTML">Telegram HTML</option>
                    </select>
                </FieldGroup>
            </div>

            <FieldGroup label="Message body" required>
                <textarea ref="bodyEl" v-model="form.body" class="tg-textarea" />
            </FieldGroup>

            <FieldGroup v-if="formTags.length" label="Form fields — click to insert">
                <div class="tg-chips">
                    <button
                        v-for="tag in formTags"
                        :key="tag.name"
                        type="button"
                        class="tg-chip"
                        :title="tag.type"
                        @click="insertTag(tag.name)"
                    >
                        [{{ tag.name }}]
                    </button>
                </div>
            </FieldGroup>

            <FieldGroup label="Special tags — click to insert">
                <div class="tg-chips">
                    <button
                        v-for="(description, tag) in store.specialTags"
                        :key="tag"
                        type="button"
                        class="tg-chip"
                        :title="description"
                        @click="insertTag(tag)"
                    >
                        [{{ tag }}]
                    </button>
                </div>
            </FieldGroup>
        </div>

        <template #footer="{ close }">
            <button type="button" class="b418-btn b418-btn--secondary" @click="close">Cancel</button>
            <button type="button" class="b418-btn b418-btn--primary" :disabled="saving || !form.name || !form.body" @click="save">
                <span v-if="saving" class="b418-btn__spinner" />
                {{ saving ? 'Saving…' : 'Save template' }}
            </button>
        </template>
    </Modal>

    <ConfirmDialog
        v-model="confirmOpen"
        title="Delete template"
        :message="`Delete “${pendingDelete?.name}”? Connections using it will fall back to the default template.`"
        @confirm="doDelete"
    />
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { Card, Modal, Alert, Badge } from '@branch418/shared/components';
import { useWordpressAjax } from '@branch418/shared/composables';
import FieldGroup from './ui/FieldGroup.vue';
import ConfirmDialog from './ui/ConfirmDialog.vue';
import { useStore, formById, formTitle } from '../composables/useStore.js';
import { useNotice } from '../composables/useNotice.js';

const store = useStore();
const { request } = useWordpressAjax();
const { notice, show, dismiss } = useNotice();

const modalOpen = ref(false);
const modalError = ref('');
const saving = ref(false);
const showDefault = ref(false);
const bodyEl = ref(null);
const form = ref(emptyForm());

const confirmOpen = ref(false);
const pendingDelete = ref(null);

const formTags = computed(() => formById(store, form.value.form_id)?.tags || []);

// React to guide/checklist navigation ("add-template" opens the modal).
watch(
    () => store.pendingAction,
    (action) => {
        if (action === 'add-template') {
            store.pendingAction = '';
            openModal();
        }
    },
    { immediate: true }
);

function emptyForm() {
    return { id: '', name: '', form_id: 0, parse_mode: 'none', body: store.defaultTemplate };
}

function excerpt(body) {
    const flat = body.replace(/\s+/g, ' ').trim();
    return flat.length > 90 ? flat.slice(0, 90) + '…' : flat;
}

function openModal(tpl = null) {
    form.value = tpl ? { ...tpl } : emptyForm();
    modalError.value = '';
    modalOpen.value = true;
}

function insertTag(tag) {
    const el = bodyEl.value;
    const token = `[${tag}]`;

    if (!el) {
        form.value.body += token;
        return;
    }

    const start = el.selectionStart ?? form.value.body.length;
    const end = el.selectionEnd ?? start;
    form.value.body = form.value.body.slice(0, start) + token + form.value.body.slice(end);

    nextTick(() => {
        el.focus();
        el.selectionStart = el.selectionEnd = start + token.length;
    });
}

async function save() {
    saving.value = true;
    modalError.value = '';
    try {
        const data = await request('b418_wp_tg_cf7_save_template', { template: form.value });
        store.templates = data.templates;
        modalOpen.value = false;
        show('success', 'Template saved.');
    } catch (e) {
        modalError.value = e.message;
    } finally {
        saving.value = false;
    }
}

function askDelete(tpl) {
    pendingDelete.value = tpl;
    confirmOpen.value = true;
}

async function doDelete() {
    try {
        const data = await request('b418_wp_tg_cf7_delete_template', { id: pendingDelete.value.id });
        store.templates = data.templates;
        show('success', 'Template deleted.');
    } catch (e) {
        show('danger', e.message);
    }
}
</script>
