<template>
    <Card
        title="Connections"
        description="Route forms to Telegram: pick a form, a bot, one or more chats, and an optional message template."
    >
        <template #actions>
            <button
                type="button"
                class="b418-btn b418-btn--primary b418-btn--sm"
                :disabled="!canCreate"
                @click="openModal()"
            >
                + Add connection
            </button>
        </template>

        <Alert v-if="notice" :variant="notice.variant" dismissible @dismiss="dismiss">
            {{ notice.text }}
        </Alert>

        <Alert v-if="!canCreate" variant="info" title="First things first">
            <p style="margin: 0 0 10px;">
                A connection needs a bot to send with and a chat to deliver to. Set those up first:
            </p>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button
                    v-if="!store.bots.length"
                    type="button"
                    class="b418-btn b418-btn--primary b418-btn--sm"
                    @click="navigate(store, 'bots', 'add-bot')"
                >
                    1. Add a bot
                </button>
                <button
                    v-if="!store.chats.length"
                    type="button"
                    class="b418-btn b418-btn--sm"
                    :class="store.bots.length ? 'b418-btn--primary' : 'b418-btn--secondary'"
                    @click="navigate(store, 'bots', 'add-chat')"
                >
                    {{ store.bots.length ? '' : '2. ' }}Add a chat
                </button>
            </div>
        </Alert>

        <EmptyState
            v-else-if="!store.rules.length"
            title="No connections yet"
            description="Nothing is being sent to Telegram. Create your first connection to start receiving submissions."
        >
            <template #action>
                <button type="button" class="b418-btn b418-btn--primary" @click="openModal()">
                    Create connection
                </button>
            </template>
        </EmptyState>

        <div v-if="store.rules.length" class="tg-list">
            <div
                v-for="rule in store.rules"
                :key="rule.id"
                class="tg-item"
                :class="{ 'tg-item--disabled': !rule.enabled }"
            >
                <ToggleSwitch :model-value="rule.enabled" @update:model-value="toggleRule(rule, $event)" />
                <div class="tg-item__main">
                    <div class="tg-item__title">
                        {{ rule.name || formTitle(store, rule.form_id) }}
                        <Badge v-if="!rule.form_id" variant="accent" size="sm">all forms</Badge>
                        <Badge v-if="brokenParts(rule)" variant="danger" size="sm">{{ brokenParts(rule) }}</Badge>
                    </div>
                    <div class="tg-item__meta">
                        <span>{{ formTitle(store, rule.form_id) }}</span>
                        <svg class="tg-arrow" width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M2 8h11m0 0L9.5 4.5M13 8l-3.5 3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>{{ botName(rule) }}</span>
                        <svg class="tg-arrow" width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M2 8h11m0 0L9.5 4.5M13 8l-3.5 3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>{{ chatNames(rule) }}</span>
                        <Badge size="sm">{{ templateName(rule) }}</Badge>
                    </div>
                </div>
                <div class="tg-item__actions">
                    <button type="button" class="tg-icon-btn" title="Send test message" :disabled="testingId === rule.id" @click="sendTest(rule)">
                        <svg v-if="testingId !== rule.id" width="15" height="15" viewBox="0 0 20 20" fill="none"><path d="M18 2L9 11m9-9l-5.5 16L9 11m9-9L2 7.5 9 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span v-else class="b418-btn__spinner" style="border-color: rgba(124,58,237,.25); border-top-color: #7C3AED;" />
                    </button>
                    <button type="button" class="tg-icon-btn" title="Edit" @click="openModal(rule)">
                        <svg width="15" height="15" viewBox="0 0 20 20" fill="none"><path d="M13.5 3.5l3 3L7 16H4v-3l9.5-9.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                    </button>
                    <button type="button" class="tg-icon-btn tg-icon-btn--danger" title="Delete" @click="askDelete(rule)">
                        <svg width="15" height="15" viewBox="0 0 20 20" fill="none"><path d="M4 6h12M8 6V4h4v2m-6.5 0l.6 10h7.8l.6-10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </Card>

    <Modal v-model="modalOpen" :title="form.id ? 'Edit connection' : 'Add connection'" size="lg">
        <div class="tg-form">
            <Alert v-if="modalError" variant="danger">{{ modalError }}</Alert>

            <FieldGroup label="Name" hint="Auto-generated from the form and chats — type your own to override, clear to go back to auto.">
                <input :value="form.name" type="text" class="tg-input" placeholder="e.g. Contact page → Sales group" @input="onNameInput" />
            </FieldGroup>

            <div class="tg-row">
                <FieldGroup label="Contact form" required>
                    <select v-model="form.form_id" class="tg-select">
                        <option :value="0">All forms</option>
                        <option v-for="f in store.forms" :key="f.id" :value="f.id">{{ f.title }}</option>
                    </select>
                </FieldGroup>
                <FieldGroup label="Send with bot" required>
                    <select v-model="form.bot_id" class="tg-select">
                        <option value="" disabled>Choose a bot…</option>
                        <option v-for="bot in store.bots" :key="bot.id" :value="bot.id">{{ bot.name }}</option>
                    </select>
                </FieldGroup>
            </div>

            <FieldGroup label="Deliver to chats" required hint="The bot must be a member (or admin) of every selected chat.">
                <div class="tg-check-list">
                    <label v-for="chat in store.chats" :key="chat.id" class="tg-check">
                        <input v-model="form.chat_ids" type="checkbox" :value="chat.id" />
                        {{ chat.name }}
                        <span class="tg-code">{{ chat.chat_id }}</span>
                    </label>
                </div>
            </FieldGroup>

            <FieldGroup
                label="Message template"
                hint="Templates are managed in the “Templates” tab. The default template sends all submitted fields."
            >
                <select v-model="form.template_id" class="tg-select">
                    <option value="">Default template (all fields)</option>
                    <option v-for="tpl in availableTemplates" :key="tpl.id" :value="tpl.id">
                        {{ tpl.name }}{{ tpl.form_id ? '' : ' (any form)' }}
                    </option>
                </select>
            </FieldGroup>

            <FieldGroup label="Enabled">
                <ToggleSwitch v-model="form.enabled" />
            </FieldGroup>
        </div>

        <template #footer="{ close }">
            <button type="button" class="b418-btn b418-btn--secondary" @click="close">Cancel</button>
            <button
                type="button"
                class="b418-btn b418-btn--primary"
                :disabled="saving || !form.bot_id || !form.chat_ids.length"
                @click="save"
            >
                <span v-if="saving" class="b418-btn__spinner" />
                {{ saving ? 'Saving…' : 'Save connection' }}
            </button>
        </template>
    </Modal>

    <ConfirmDialog
        v-model="confirmOpen"
        title="Delete connection"
        :message="`Delete this connection? Submissions of “${formTitle(store, pendingDelete?.form_id)}” will no longer be sent to Telegram through it.`"
        @confirm="doDelete"
    />
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Card, Modal, Alert, Badge, EmptyState } from '@branch418/shared/components';
import { useWordpressAjax } from '@branch418/shared/composables';
import FieldGroup from './ui/FieldGroup.vue';
import ToggleSwitch from './ui/ToggleSwitch.vue';
import ConfirmDialog from './ui/ConfirmDialog.vue';
import { useStore, navigate, botById, chatById, templateById, formTitle } from '../composables/useStore.js';
import { useNotice } from '../composables/useNotice.js';

const store = useStore();
const { request } = useWordpressAjax();
const { notice, show, dismiss } = useNotice();

const modalOpen = ref(false);
const modalError = ref('');
const saving = ref(false);
const testingId = ref('');
const form = ref(emptyForm());

const confirmOpen = ref(false);
const pendingDelete = ref(null);

const canCreate = computed(() => store.bots.length > 0 && store.chats.length > 0);

// React to guide/checklist navigation ("add-connection" opens the modal).
watch(
    () => store.pendingAction,
    (action) => {
        if (action === 'add-connection') {
            store.pendingAction = '';
            if (canCreate.value) {
                openModal();
            }
        }
    },
    { immediate: true }
);

const availableTemplates = computed(() =>
    store.templates.filter((tpl) => !tpl.form_id || Number(tpl.form_id) === Number(form.value.form_id))
);

// Keep the name in sync with the selected form/chats until the user
// types their own; an emptied field hands naming back to auto mode.
const nameEdited = ref(false);

function autoName() {
    const chats = form.value.chat_ids
        .map((id) => chatById(store, id)?.name)
        .filter(Boolean);
    const title = formTitle(store, form.value.form_id);
    return chats.length ? `${title} → ${chats.join(', ')}` : title;
}

function onNameInput(event) {
    form.value.name = event.target.value;
    nameEdited.value = form.value.name.trim() !== '';
}

watch(
    () => [form.value.form_id, form.value.chat_ids.join(',')],
    () => {
        if (!modalOpen.value || nameEdited.value) {
            return;
        }
        // Untouched modal ("All forms", no chats yet) — nothing to name.
        if (!Number(form.value.form_id) && !form.value.chat_ids.length) {
            return;
        }
        form.value.name = autoName();
    }
);

function emptyForm() {
    return { id: '', name: '', form_id: 0, bot_id: '', chat_ids: [], template_id: '', enabled: true };
}

function botName(rule) {
    return botById(store, rule.bot_id)?.name || 'Missing bot';
}

function chatNames(rule) {
    const names = rule.chat_ids
        .map((id) => chatById(store, id)?.name)
        .filter(Boolean);
    return names.length ? names.join(', ') : 'No chats';
}

function templateName(rule) {
    if (!rule.template_id) {
        return 'Default template';
    }
    return templateById(store, rule.template_id)?.name || 'Missing template';
}

function brokenParts(rule) {
    if (!botById(store, rule.bot_id)) {
        return 'bot missing';
    }
    if (!rule.chat_ids.some((id) => chatById(store, id))) {
        return 'no valid chats';
    }
    return '';
}

function openModal(rule = null) {
    form.value = rule
        ? { ...rule, chat_ids: [...rule.chat_ids] }
        : emptyForm();
    nameEdited.value = !!rule;
    modalError.value = '';
    modalOpen.value = true;
}

async function save() {
    saving.value = true;
    modalError.value = '';
    try {
        const data = await request('b418_wp_tg_cf7_save_rule', { rule: { ...form.value, name: form.value.name.trim() } });
        store.rules = data.rules;
        modalOpen.value = false;
        show('success', 'Connection saved.');
    } catch (e) {
        modalError.value = e.message;
    } finally {
        saving.value = false;
    }
}

async function toggleRule(rule, enabled) {
    try {
        const data = await request('b418_wp_tg_cf7_save_rule', { rule: { ...rule, enabled } });
        store.rules = data.rules;
    } catch (e) {
        show('danger', e.message);
    }
}

async function sendTest(rule) {
    testingId.value = rule.id;
    try {
        const data = await request('b418_wp_tg_cf7_test_rule', { id: rule.id });
        show('success', data.message);
    } catch (e) {
        show('danger', e.message);
    } finally {
        testingId.value = '';
    }
}

function askDelete(rule) {
    pendingDelete.value = rule;
    confirmOpen.value = true;
}

async function doDelete() {
    try {
        const data = await request('b418_wp_tg_cf7_delete_rule', { id: pendingDelete.value.id });
        store.rules = data.rules;
        show('success', 'Connection deleted.');
    } catch (e) {
        show('danger', e.message);
    }
}
</script>
