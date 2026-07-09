<template>
    <Card
        title="Activity Log"
        description="The most recent Telegram delivery attempts, newest first."
    >
        <template #actions>
            <button
                v-if="store.log.length"
                type="button"
                class="b418-btn b418-btn--secondary b418-btn--sm"
                :disabled="clearing"
                @click="confirmOpen = true"
            >
                Clear log
            </button>
        </template>

        <Alert v-if="notice" :variant="notice.variant" dismissible @dismiss="dismiss">
            {{ notice.text }}
        </Alert>

        <EmptyState
            v-if="!store.log.length"
            title="No activity yet"
            description="Deliveries will show up here as soon as a connected form is submitted (or you send a test)."
        />

        <div v-else class="tg-table-wrap">
            <table class="tg-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Form</th>
                        <th>Chat</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(entry, index) in store.log" :key="index">
                        <td style="white-space: nowrap;">{{ formatTime(entry.time) }}</td>
                        <td>{{ entry.form_title }}</td>
                        <td>{{ entry.chat_name }}</td>
                        <td>
                            <Badge :variant="entry.ok ? 'success' : 'danger'" size="sm" dot>
                                {{ entry.ok ? 'Delivered' : 'Failed' }}
                            </Badge>
                            <div v-if="entry.error" class="tg-field__hint" style="margin-top: 4px;">{{ entry.error }}</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </Card>

    <ConfirmDialog
        v-model="confirmOpen"
        title="Clear activity log"
        message="Remove all log entries? This cannot be undone."
        confirm-label="Clear log"
        @confirm="clearLog"
    />
</template>

<script setup>
import { ref } from 'vue';
import { Card, Alert, Badge, EmptyState } from '@branch418/shared/components';
import { useWordpressAjax } from '@branch418/shared/composables';
import ConfirmDialog from './ui/ConfirmDialog.vue';
import { useStore } from '../composables/useStore.js';
import { useNotice } from '../composables/useNotice.js';

const store = useStore();
const { request } = useWordpressAjax();
const { notice, show, dismiss } = useNotice();

const clearing = ref(false);
const confirmOpen = ref(false);

function formatTime(timestamp) {
    return new Date(timestamp * 1000).toLocaleString();
}

async function clearLog() {
    clearing.value = true;
    try {
        await request('b418_wp_tg_cf7_clear_log', {});
        store.log = [];
        show('success', 'Log cleared.');
    } catch (e) {
        show('danger', e.message);
    } finally {
        clearing.value = false;
    }
}
</script>
