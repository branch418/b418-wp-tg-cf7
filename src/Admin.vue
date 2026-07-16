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
                <SetupGuide v-if="store.guideOpen" @close="hideGuide" />

                <div class="tg-tabbar">
                    <nav class="tg-tabs">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="tg-tabs__tab"
                            :class="{ 'tg-tabs__tab--active': store.activeTab === tab.key }"
                            @click="store.activeTab = tab.key"
                        >
                            {{ tab.label }}
                            <span v-if="tab.count()" class="tg-tabs__count">{{ tab.count() }}</span>
                        </button>
                    </nav>
                    <button v-if="!store.guideOpen" type="button" class="b418-btn b418-btn--ghost" @click="store.guideOpen = true">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.75" stroke="currentColor" stroke-width="1.3"/><path d="M6.3 6.1a1.75 1.75 0 1 1 2.4 1.9c-.5.2-.7.5-.7 1v.3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><circle cx="8" cy="11.4" r="0.7" fill="currentColor"/></svg>
                        Setup guide
                    </button>
                </div>

                <template v-if="store.activeTab === 'bots'">
                    <BotsSection />
                    <ChatsSection />
                </template>
                <ConnectionsPanel v-else-if="store.activeTab === 'connections'" />
                <TemplatesPanel v-else-if="store.activeTab === 'templates'" />
                <LogPanel v-else-if="store.activeTab === 'log'" />
                <component v-else-if="pro?.panels?.[store.activeTab]" :is="pro.panels[store.activeTab]" />
            </template>
        </Column>

        <Column width="2/6" type="sidebar">
            <Section title="Status">
                <Card>
                    <SidebarStats :items="statsItems" :loading="store.loading" />
                </Card>
            </Section>

            <Section title="Setup checklist">
                <Card variant="muted">
                    <div v-if="!store.loading">
                        <button
                            v-for="(step, index) in steps"
                            :key="step.key"
                            type="button"
                            class="tg-check-row"
                            :disabled="step.locked"
                            @click="go(step)"
                        >
                            <span class="tg-check-row__icon" :class="{ 'tg-check-row__icon--done': step.done }">
                                <svg v-if="step.done" width="11" height="11" viewBox="0 0 14 14" fill="none"><path d="M2.5 7.5l3 3 6-6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <template v-else>{{ index + 1 }}</template>
                            </span>
                            <span class="tg-check-row__label">{{ step.title }}</span>
                            <svg class="tg-check-row__chev" width="12" height="12" viewBox="0 0 16 16" fill="none"><path d="M6 3.5L10.5 8 6 12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                    <Alert v-if="!store.loading && setupComplete" variant="success" hide-icon>
                        Setup complete — submissions are flowing to Telegram.
                    </Alert>
                </Card>
            </Section>

            <Section v-if="!pro" title="Go further">
                <Card variant="highlighted" title="Pro version — coming soon">
                    <ul class="tg-pro-list">
                        <li>File &amp; image attachments forwarded to Telegram</li>
                        <li>Conditional routing based on field values</li>
                        <li>Two-way replies from Telegram to visitors</li>
                        <li>Delivery retries &amp; full submission history</li>
                    </ul>
                </Card>
            </Section>
        </Column>
    </AdminLayout>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import {
    AdminLayout, Column, Section, Card, Alert, SidebarStats, SkeletonGroup,
} from '@branch418/shared/components';
import { useWordpressAjax } from '@branch418/shared/composables';
import { provideStore } from './composables/useStore.js';
import { useSetupSteps } from './composables/useSetupSteps.js';
import { usePro } from './composables/usePro.js';
import SetupGuide from './components/SetupGuide.vue';
import ConnectionsPanel from './components/ConnectionsPanel.vue';
import BotsSection from './components/BotsSection.vue';
import ChatsSection from './components/ChatsSection.vue';
import TemplatesPanel from './components/TemplatesPanel.vue';
import LogPanel from './components/LogPanel.vue';

const GUIDE_HIDDEN_KEY = 'b418WpTgCf7GuideHidden';

const store = provideStore();
const { request } = useWordpressAjax();
const { steps, setupComplete, go } = useSetupSteps(store);
const pro = usePro();

// Ordered to match the setup flow: ingredients → routing → message → monitoring.
const baseTabs = [
    { key: 'bots', label: 'Bots & Chats', count: () => store.bots.length + store.chats.length },
    { key: 'connections', label: 'Connections', count: () => store.rules.length },
    { key: 'templates', label: 'Templates', count: () => store.templates.length },
    { key: 'log', label: 'Activity Log', count: () => store.log.length },
];
const tabs = pro?.tabs ? pro.tabs(baseTabs, store) : baseTabs;

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

// Once the last required step is finished the guide has done its job —
// hide it right away instead of waiting for the next page load. Manual
// re-opens stay open: the watcher only fires when completion flips.
watch(setupComplete, (complete) => {
    if (complete && store.guideOpen) {
        store.guideOpen = false;
    }
});

function hideGuide() {
    store.guideOpen = false;
    try {
        localStorage.setItem(GUIDE_HIDDEN_KEY, '1');
    } catch (e) {
        // Private mode / storage disabled — dismissal just won't persist.
    }
}

onMounted(async () => {
    try {
        const data = await request('b418_wp_tg_cf7_bootstrap', {});
        Object.assign(store, data);

        const complete = data.bots.length && data.chats.length && data.rules.length;
        store.activeTab = complete ? 'connections' : 'bots';

        let guideHidden = false;
        try {
            guideHidden = localStorage.getItem(GUIDE_HIDDEN_KEY) === '1';
        } catch (e) {
            // Storage unavailable — treat as not hidden.
        }
        store.guideOpen = !complete && !guideHidden;
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
