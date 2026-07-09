import { computed } from 'vue';
import { navigate } from './useStore.js';

/**
 * Single source of truth for the guided setup flow. Used by the setup
 * guide card, the sidebar checklist, and cross-links in empty states —
 * every step knows its live completion state and where to jump.
 */
export function useSetupSteps(store) {
    const steps = computed(() => {
        const hasBots = store.bots.length > 0;
        const hasChats = store.chats.length > 0;
        const hasRules = store.rules.length > 0;

        return [
            {
                key: 'bot',
                title: 'Create a bot',
                description: 'Open @BotFather in Telegram, send /newbot, and paste the token here — it becomes the sender of your notifications.',
                done: hasBots,
                locked: false,
                optional: false,
                actionLabel: hasBots ? 'Add another bot' : 'Add a bot',
                tab: 'bots',
                action: 'add-bot',
            },
            {
                key: 'chat',
                title: 'Add a chat',
                description: 'Invite the bot to your group or channel (channels: as admin), send any message there, and let the plugin detect the chat ID.',
                done: hasChats,
                locked: false,
                optional: false,
                actionLabel: hasChats ? 'Add another chat' : 'Add a chat',
                tab: 'bots',
                action: 'add-chat',
            },
            {
                key: 'connection',
                title: 'Connect a form',
                description: 'Pick which Contact Form 7 form goes to which chats, and with which bot. From then on every submission lands in Telegram.',
                done: hasRules,
                locked: !hasBots || !hasChats,
                optional: false,
                actionLabel: hasRules ? 'Add another connection' : 'Create connection',
                tab: 'connections',
                action: 'add-connection',
            },
            {
                key: 'template',
                title: 'Craft the message',
                description: 'Optional: design what arrives in Telegram per form — pick fields, add formatting. Without a template, all fields are sent.',
                done: store.templates.length > 0,
                locked: false,
                optional: true,
                actionLabel: 'Add a template',
                tab: 'templates',
                action: 'add-template',
            },
        ];
    });

    const setupComplete = computed(() =>
        steps.value.every((step) => step.done || step.optional)
    );

    // The step to nudge the user toward next.
    const currentStep = computed(() =>
        steps.value.find((step) => !step.done && !step.optional)?.key || ''
    );

    function go(step) {
        if (step.locked) {
            return;
        }
        navigate(store, step.tab, step.action);
    }

    return { steps, setupComplete, currentStep, go };
}
