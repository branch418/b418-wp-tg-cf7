<template>
    <Card
        title="Chats & Channels"
        description="Destinations for your messages: private chats, groups, channels, or forum topics. Add the bot to the chat first, then detect or enter its ID."
    >
        <template #actions>
            <button type="button" class="b418-btn b418-btn--primary b418-btn--sm" @click="openModal()">
                + Add chat
            </button>
        </template>

        <Alert v-if="notice" :variant="notice.variant" dismissible @dismiss="dismiss">
            {{ notice.text }}
        </Alert>

        <EmptyState
            v-if="!store.chats.length"
            title="No chats yet"
            description="Add the chat, group, or channel where form submissions should arrive."
        >
            <template #action>
                <button type="button" class="b418-btn b418-btn--primary" @click="openModal()">
                    Add your first chat
                </button>
            </template>
        </EmptyState>

        <div v-else class="tg-list">
            <div v-for="chat in store.chats" :key="chat.id" class="tg-item">
                <div class="tg-item__main">
                    <div class="tg-item__title">
                        {{ chat.name }}
                        <Badge v-if="chat.thread_id" variant="accent" size="sm">topic {{ chat.thread_id }}</Badge>
                    </div>
                    <div class="tg-item__meta">
                        <span class="tg-code">{{ chat.chat_id }}</span>
                    </div>
                </div>
                <div class="tg-item__actions">
                    <button type="button" class="tg-icon-btn" title="Edit" @click="openModal(chat)">
                        <svg width="15" height="15" viewBox="0 0 20 20" fill="none"><path d="M13.5 3.5l3 3L7 16H4v-3l9.5-9.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                    </button>
                    <button type="button" class="tg-icon-btn tg-icon-btn--danger" title="Delete" @click="askDelete(chat)">
                        <svg width="15" height="15" viewBox="0 0 20 20" fill="none"><path d="M4 6h12M8 6V4h4v2m-6.5 0l.6 10h7.8l.6-10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </Card>

    <Modal v-model="modalOpen" :title="form.id ? 'Edit chat' : 'Add chat'" size="lg">
        <div class="tg-form">
            <Alert v-if="modalError" variant="danger">{{ modalError }}</Alert>

            <FieldGroup label="Name" required hint="A label for this chat, e.g. “Sales team group”.">
                <input v-model.trim="form.name" type="text" class="tg-input" placeholder="e.g. Sales team group" />
            </FieldGroup>

            <div class="tg-row">
                <FieldGroup label="Chat ID" required hint="Numeric ID (groups start with -100…) or @channelusername for public channels.">
                    <input v-model.trim="form.chat_id" type="text" class="tg-input" placeholder="-1001234567890 or @mychannel" />
                </FieldGroup>
                <FieldGroup label="Topic (thread) ID" hint="Optional — only for topics inside forum supergroups.">
                    <input v-model.trim="form.thread_id" type="text" class="tg-input" placeholder="e.g. 42" />
                </FieldGroup>
            </div>

            <Spoiler title="Don't know the chat ID? Detect it" :open="false">
                <div class="tg-form">
                    <p class="tg-field__hint" style="margin: 0;">
                        Add your bot to the chat (channels: as administrator), send any message there,
                        then pick the bot below. Note: detection doesn't work if the bot uses webhooks.
                    </p>
                    <div class="tg-row">
                        <select v-model="detectBotId" class="tg-select">
                            <option value="" disabled>Choose a bot…</option>
                            <option v-for="bot in store.bots" :key="bot.id" :value="bot.id">{{ bot.name }}</option>
                        </select>
                        <button type="button" class="b418-btn b418-btn--secondary" :disabled="!detectBotId || detecting" @click="detectChats">
                            <span v-if="detecting" class="b418-btn__spinner" />
                            {{ detecting ? 'Scanning…' : 'Detect chats' }}
                        </button>
                    </div>
                    <div v-if="detected.length" class="tg-list">
                        <div v-for="found in detected" :key="found.chat_id" class="tg-item">
                            <div class="tg-item__main">
                                <div class="tg-item__title">{{ found.title || 'Untitled chat' }}
                                    <Badge size="sm">{{ found.type }}</Badge>
                                </div>
                                <div class="tg-item__meta"><span class="tg-code">{{ found.chat_id }}</span></div>
                            </div>
                            <div class="tg-item__actions">
                                <button type="button" class="b418-btn b418-btn--secondary b418-btn--sm" @click="useDetected(found)">
                                    Use this chat
                                </button>
                            </div>
                        </div>
                    </div>
                    <Alert v-else-if="detectedEmpty" variant="warning">
                        No chats found. Send a message in the target chat (with the bot present) and try again.
                    </Alert>
                </div>
            </Spoiler>

            <FieldGroup label="Send a test message" hint="Verifies that the selected bot can post to this chat before you save.">
                <div class="tg-row">
                    <select v-model="testBotId" class="tg-select">
                        <option value="" disabled>Test with bot…</option>
                        <option v-for="bot in store.bots" :key="bot.id" :value="bot.id">{{ bot.name }}</option>
                    </select>
                    <button type="button" class="b418-btn b418-btn--secondary" :disabled="!testBotId || !form.chat_id || testing" @click="sendTest">
                        <span v-if="testing" class="b418-btn__spinner" />
                        {{ testing ? 'Sending…' : 'Send test' }}
                    </button>
                </div>
            </FieldGroup>
            <Alert v-if="testResult" :variant="testResult.variant">{{ testResult.text }}</Alert>
        </div>

        <template #footer="{ close }">
            <button type="button" class="b418-btn b418-btn--secondary" @click="close">Cancel</button>
            <button type="button" class="b418-btn b418-btn--primary" :disabled="saving || !form.name || !form.chat_id" @click="save">
                <span v-if="saving" class="b418-btn__spinner" />
                {{ saving ? 'Saving…' : 'Save chat' }}
            </button>
        </template>
    </Modal>

    <ConfirmDialog
        v-model="confirmOpen"
        title="Delete chat"
        :message="`Delete “${pendingDelete?.name}”? It will be removed from any connections that use it.`"
        @confirm="doDelete"
    />
</template>

<script setup>
import { ref } from 'vue';
import { Card, Modal, Alert, Badge, EmptyState, Spoiler } from '@branch418/shared/components';
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
const form = ref({ id: '', name: '', chat_id: '', thread_id: '' });

const detectBotId = ref('');
const detecting = ref(false);
const detected = ref([]);
const detectedEmpty = ref(false);

const testBotId = ref('');
const testing = ref(false);
const testResult = ref(null);

const confirmOpen = ref(false);
const pendingDelete = ref(null);

function openModal(chat = null) {
    form.value = chat
        ? { id: chat.id, name: chat.name, chat_id: chat.chat_id, thread_id: chat.thread_id }
        : { id: '', name: '', chat_id: '', thread_id: '' };
    modalError.value = '';
    testResult.value = null;
    detected.value = [];
    detectedEmpty.value = false;
    modalOpen.value = true;
}

async function detectChats() {
    detecting.value = true;
    detectedEmpty.value = false;
    modalError.value = '';
    try {
        const data = await request('b418_wp_tg_cf7_detect_chats', { bot_id: detectBotId.value });
        detected.value = data.found;
        detectedEmpty.value = !data.found.length;
    } catch (e) {
        modalError.value = e.message;
    } finally {
        detecting.value = false;
    }
}

function useDetected(found) {
    form.value.chat_id = found.chat_id;
    if (!form.value.name && found.title) {
        form.value.name = found.title;
    }
}

async function sendTest() {
    testing.value = true;
    testResult.value = null;
    try {
        const data = await request('b418_wp_tg_cf7_test_chat', {
            bot_id: testBotId.value,
            chat: form.value,
        });
        testResult.value = { variant: 'success', text: data.message };
    } catch (e) {
        testResult.value = { variant: 'danger', text: e.message };
    } finally {
        testing.value = false;
    }
}

async function save() {
    saving.value = true;
    modalError.value = '';
    try {
        const data = await request('b418_wp_tg_cf7_save_chat', { chat: form.value });
        store.chats = data.chats;
        modalOpen.value = false;
        show('success', 'Chat saved.');
    } catch (e) {
        modalError.value = e.message;
    } finally {
        saving.value = false;
    }
}

function askDelete(chat) {
    pendingDelete.value = chat;
    confirmOpen.value = true;
}

async function doDelete() {
    try {
        const data = await request('b418_wp_tg_cf7_delete_chat', { id: pendingDelete.value.id });
        store.chats = data.chats;
        show('success', 'Chat deleted.');
    } catch (e) {
        show('danger', e.message);
    }
}
</script>
