<template>
    <AdminLayout
        pluginName="Telegram for Contact Form 7"
        pluginDescription="Send form submissions to Telegram chats and channels — by branch418"
    >
        <Column width="4/6" type="column">
            <Alert v-if="store.loadError" variant="danger" title="Could not load settings">
                {{ store.loadError }}
            </Alert>

            <Alert v-else-if="!store.loading && !store.cf7Active" variant="danger" title="Contact Form 7 is not active">
                This plugin needs the Contact Form 7 plugin. Install and activate it, then reload this page.
            </Alert>

            <template v-if="store.loading">
                <Card><SkeletonGroup preset="card" :count="3" /></Card>
            </template>

            <template v-else-if="!store.loadError">
                <nav class="tg-tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="tg-tabs__tab"
                        :class="{ 'tg-tabs__tab--active': activeTab === tab.key }"
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                        <span v-if="tab.count()" class="tg-tabs__count">{{ tab.count() }}</span>
                    </button>
                </nav>

                <ConnectionsPanel v-if="activeTab === 'connections'" />
                <template v-else-if="activeTab === 'bots'">
                    <BotsSection />
                    <ChatsSection />
                </template>
                <TemplatesPanel v-else-if="activeTab === 'templates'" />
                <LogPanel v-else-if="activeTab === 'log'" />
            </template>
        </Column>

        <Column width="2/6" type="sidebar">
            <Section title="Status">
                <Card>
                    <SidebarStats :items="statsItems" :loading="store.loading" />
                </Card>
            </Section>

            <Section title="Quick start">
                <Card variant="muted">
                    <ol class="tg-steps">
                        <li><b>Add a bot</b> — create one with @BotFather and paste the token.</li>
                        <li><b>Add a chat</b> — invite the bot, then detect or enter the chat ID.</li>
                        <li><b>Connect a form</b> — pick form, bot and chats in “Connections”.</li>
                        <li><b>Customize</b> — optionally craft a message template per form.</li>
                    </ol>
                </Card>
            </Section>

            <Section title="Go further">
                <Card variant="highlighted" title="Pro version — coming soon">
                    <ul class="tg-pro-list">
                        <li>File &amp; image attachments forwarded to Telegram</li>
                        <li>Conditional routing based on field values</li>
                        <li>Two-way replies from Telegram to visitors</li>
                        <li>Delivery retries &amp; full submission history</li>
                    </ul>
                    <p class="tg-field__hint" style="margin: 0;">
                        The Pro add-on plugs into this plugin's hook API — your bots, chats and
                        connections carry over unchanged.
                    </p>
                </Card>
            </Section>
        </Column>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
    AdminLayout, Column, Section, Card, Alert, SidebarStats, SkeletonGroup,
} from '@branch418/shared/components';
import { useWordpressAjax } from '@branch418/shared/composables';
import { provideStore } from './composables/useStore.js';
import ConnectionsPanel from './components/ConnectionsPanel.vue';
import BotsSection from './components/BotsSection.vue';
import ChatsSection from './components/ChatsSection.vue';
import TemplatesPanel from './components/TemplatesPanel.vue';
import LogPanel from './components/LogPanel.vue';

const store = provideStore();
const { request } = useWordpressAjax();

const activeTab = ref('connections');

const tabs = [
    { key: 'connections', label: 'Connections', count: () => store.rules.length },
    { key: 'bots', label: 'Bots & Chats', count: () => store.bots.length + store.chats.length },
    { key: 'templates', label: 'Templates', count: () => store.templates.length },
    { key: 'log', label: 'Activity Log', count: () => store.log.length },
];

const statsItems = computed(() => {
    const delivered = store.log.filter((entry) => entry.ok).length;
    const failed = store.log.length - delivered;
    const lastEntry = store.log[0];

    return [
        {
            label: 'Contact Form 7',
            type: 'status',
            value: store.cf7Active ? 'Active' : 'Missing',
            variant: store.cf7Active ? 'success' : 'danger',
        },
        { label: 'Bots', value: store.bots.length },
        { label: 'Chats', value: store.chats.length },
        {
            label: 'Connections',
            type: 'status',
            value: `${store.rules.filter((rule) => rule.enabled).length} active`,
            variant: store.rules.some((rule) => rule.enabled) ? 'success' : 'neutral',
        },
        { label: 'Delivered (recent)', value: delivered, variant: 'success' },
        { label: 'Failed (recent)', value: failed, variant: failed ? 'danger' : 'neutral' },
        {
            label: 'Last delivery',
            type: 'time',
            value: lastEntry ? new Date(lastEntry.time * 1000).toLocaleString() : '—',
        },
    ];
});

onMounted(async () => {
    try {
        const data = await request('b418_wp_tg_cf7_bootstrap', {});
        Object.assign(store, data);

        if (!data.rules.length) {
            if (!data.bots.length) {
                activeTab.value = 'bots';
            }
        }
    } catch (e) {
        store.loadError = e.message;
    } finally {
        store.loading = false;
    }
});
</script>

<style lang="scss">
// Unscoped on purpose: exposes the shared .b418-btn button classes and the
// local .tg-* primitives to every child component.
@use '@branch418/shared/styles/global' as *;
@use '@branch418/shared/styles/transitions';
@use './styles/ui';
</style>
