<template>
    <Card
        title="Telegram Bots"
        description="Bots deliver your messages. Create one with @BotFather and paste its token here — the token is verified against the Telegram API on save."
    >
        <template #actions>
            <button type="button" class="b418-btn b418-btn--primary b418-btn--sm" @click="openModal()">
                + Add bot
            </button>
        </template>

        <Alert v-if="notice" :variant="notice.variant" dismissible @dismiss="dismiss">
            {{ notice.text }}
        </Alert>

        <EmptyState
            v-if="!store.bots.length"
            title="No bots yet"
            description="Open @BotFather in Telegram, send /newbot, and paste the token you receive."
        >
            <template #action>
                <button type="button" class="b418-btn b418-btn--primary" @click="openModal()">
                    Add your first bot
                </button>
            </template>
        </EmptyState>

        <div v-else class="tg-list">
            <div v-for="bot in store.bots" :key="bot.id" class="tg-item">
                <div class="tg-item__main">
                    <div class="tg-item__title">
                        {{ bot.name }}
                        <Badge v-if="bot.username" variant="info" size="sm">@{{ bot.username }}</Badge>
                    </div>
                    <div class="tg-item__meta">
                        <span class="tg-code">{{ maskToken(bot.token) }}</span>
                    </div>
                </div>
                <div class="tg-item__actions">
                    <button type="button" class="tg-icon-btn" title="Edit" @click="openModal(bot)">
                        <svg width="15" height="15" viewBox="0 0 20 20" fill="none"><path d="M13.5 3.5l3 3L7 16H4v-3l9.5-9.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                    </button>
                    <button type="button" class="tg-icon-btn tg-icon-btn--danger" title="Delete" @click="askDelete(bot)">
                        <svg width="15" height="15" viewBox="0 0 20 20" fill="none"><path d="M4 6h12M8 6V4h4v2m-6.5 0l.6 10h7.8l.6-10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </Card>

    <Modal v-model="modalOpen" :title="form.id ? 'Edit bot' : 'Add bot'">
        <div class="tg-form">
            <Alert v-if="modalError" variant="danger">{{ modalError }}</Alert>

            <FieldGroup label="Bot token" required>
                <input v-model.trim="form.token" type="text" class="tg-input" placeholder="123456789:AAF...xyz" />
                <template #hint>
                    Get a token from
                    <a href="https://t.me/BotFather" target="_blank" rel="noopener">@BotFather</a>
                    — send <code>/newbot</code> and follow the prompts.
                </template>
            </FieldGroup>

            <FieldGroup label="Name" hint="Optional label shown in this admin. Defaults to the bot's Telegram name.">
                <input v-model.trim="form.name" type="text" class="tg-input" placeholder="e.g. Sales notifications bot" />
            </FieldGroup>
        </div>

        <template #footer="{ close }">
            <button type="button" class="b418-btn b418-btn--secondary" @click="close">Cancel</button>
            <button type="button" class="b418-btn b418-btn--primary" :disabled="saving || !form.token" @click="save">
                <span v-if="saving" class="b418-btn__spinner" />
                {{ saving ? 'Verifying…' : 'Verify & save' }}
            </button>
        </template>
    </Modal>

    <ConfirmDialog
        v-model="confirmOpen"
        title="Delete bot"
        :message="`Delete “${pendingDelete?.name}”? Connections using this bot will stop delivering until you assign another bot.`"
        @confirm="doDelete"
    />
</template>

<script setup>
import { ref, watch } from 'vue';
import { Card, Modal, Alert, Badge, EmptyState } from '@branch418/shared/components';
import { useWordpressAjax } from '@branch418/shared/composables';
import FieldGroup from './ui/FieldGroup.vue';
import ConfirmDialog from './ui/ConfirmDialog.vue';
import { useStore } from '../composables/useStore.js';
import { useNotice } from '../composables/useNotice.js';

const store = useStore();
const { request } = useWordpressAjax();
const { notice, show, dismiss } = useNotice();

const modalOpen = ref(false);
const modalError = ref('');
const saving = ref(false);
const form = ref({ id: '', name: '', token: '' });

const confirmOpen = ref(false);
const pendingDelete = ref(null);

// React to guide/checklist navigation ("add-bot" opens the modal).
watch(
    () => store.pendingAction,
    (action) => {
        if (action === 'add-bot') {
            store.pendingAction = '';
            openModal();
        }
    },
    { immediate: true }
);

function maskToken(token) {
    if (!token || token.length < 12) {
        return token;
    }
    return token.slice(0, 6) + '…' + token.slice(-4);
}

function openModal(bot = null) {
    form.value = bot
        ? { id: bot.id, name: bot.name, token: bot.token }
        : { id: '', name: '', token: '' };
    modalError.value = '';
    modalOpen.value = true;
}

async function save() {
    saving.value = true;
    modalError.value = '';
    try {
        const data = await request('b418_wp_tg_cf7_save_bot', { bot: form.value });
        store.bots = data.bots;
        modalOpen.value = false;
        show('success', 'Bot verified and saved.');
    } catch (e) {
        modalError.value = e.message;
    } finally {
        saving.value = false;
    }
}

function askDelete(bot) {
    pendingDelete.value = bot;
    confirmOpen.value = true;
}

async function doDelete() {
    try {
        const data = await request('b418_wp_tg_cf7_delete_bot', { id: pendingDelete.value.id });
        store.bots = data.bots;
        show('success', 'Bot deleted.');
    } catch (e) {
        show('danger', e.message);
    }
}
</script>
