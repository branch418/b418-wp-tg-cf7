<template>
    <div class="tg-flow-wrap">
        <div class="tg-flow">
            <template v-for="(node, index) in nodes" :key="node.key">
                <div v-if="index" class="tg-flow__link" aria-hidden="true">
                    <span>{{ node.linkLabel }}</span>
                    <svg width="34" height="12" viewBox="0 0 34 12" fill="none">
                        <path d="M0 6h27m0 0l-5-4.5M27 6l-5 4.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <component
                    :is="node.tab ? 'button' : 'div'"
                    type="button"
                    class="tg-flow__node"
                    :class="{
                        'tg-flow__node--empty': node.empty,
                        'tg-flow__node--attention': node.key === attention,
                        'tg-flow__node--static': !node.tab,
                    }"
                    :title="node.tab ? 'Open ' + node.label : undefined"
                    @click="node.tab && go(node)"
                >
                    <span class="tg-flow__nicon" v-html="icons[node.icon]" />
                    <span class="tg-flow__nlabel">{{ node.label }}</span>
                    <span class="tg-flow__nstatus" :class="`tg-flow__nstatus--${node.tone}`">{{ node.status }}</span>
                </component>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useStore, navigate } from '../composables/useStore.js';

const store = useStore();

const icons = {
    form: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="4" y="3" width="16" height="18" rx="2.5" stroke="currentColor" stroke-width="1.6"/><path d="M8 8h8M8 12h8M8 16h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    route: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 6h5l8 12h5m0 0l-3-3m3 3l-3 3M3 18h5l2.2-3.3M21 6h-5l-2.2 3.3M21 6l-3-3m3 3l-3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    bot: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="5" y="8" width="14" height="11" rx="2.5" stroke="currentColor" stroke-width="1.6"/><path d="M12 8V4m0 0h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="9.5" cy="13" r="1.2" fill="currentColor"/><circle cx="14.5" cy="13" r="1.2" fill="currentColor"/><path d="M9.5 16.5h5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
    chat: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h8A2.5 2.5 0 0 1 17 5.5v5a2.5 2.5 0 0 1-2.5 2.5H9l-4 3.5V5.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M20 9v4.5a2.5 2.5 0 0 1-2.5 2.5h-.5l2 3-4.5-3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
};

const nodes = computed(() => {
    const enabledRules = store.rules.filter((rule) => rule.enabled).length;

    return [
        {
            key: 'form',
            icon: 'form',
            label: 'CF7 Form',
            status: !store.cf7Active
                ? 'CF7 inactive'
                : store.forms.length
                    ? `${store.forms.length} found`
                    : 'no forms yet',
            tone: !store.cf7Active ? 'danger' : store.forms.length ? 'ok' : 'warn',
            empty: !store.forms.length,
            tab: '', // configured in CF7 itself, not here
        },
        {
            key: 'connection',
            icon: 'route',
            label: 'Connection',
            linkLabel: 'submits to',
            status: store.rules.length ? `${enabledRules} active` : 'not set up',
            tone: enabledRules ? 'ok' : 'warn',
            empty: !store.rules.length,
            tab: 'connections',
            action: store.rules.length ? '' : 'add-connection',
        },
        {
            key: 'bot',
            icon: 'bot',
            label: 'Bot',
            linkLabel: 'sends with',
            status: store.bots.length ? `${store.bots.length} added` : 'none yet',
            tone: store.bots.length ? 'ok' : 'warn',
            empty: !store.bots.length,
            tab: 'bots',
            action: store.bots.length ? '' : 'add-bot',
        },
        {
            key: 'chats',
            icon: 'chat',
            label: 'Chats',
            linkLabel: 'delivers to',
            status: store.chats.length ? `${store.chats.length} added` : 'none yet',
            tone: store.chats.length ? 'ok' : 'warn',
            empty: !store.chats.length,
            tab: 'bots',
            action: store.chats.length ? '' : 'add-chat',
        },
    ];
});

// Pulse the node matching the next setup step (bot → chat → connection).
const attention = computed(() => {
    if (!store.bots.length) return 'bot';
    if (!store.chats.length) return 'chats';
    if (!store.rules.length) return 'connection';
    return '';
});

function go(node) {
    navigate(store, node.tab, node.action || '');
}
</script>
