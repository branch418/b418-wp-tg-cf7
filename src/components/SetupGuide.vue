<template>
    <Card variant="accent">
        <template #header>
            <div class="pd-card__header-text">
                <h3 class="pd-card__title">How it works</h3>
                <p class="pd-card__description">
                    Every submission flows left to right — click any block to jump to its settings.
                </p>
            </div>
            <div class="pd-card__header-actions">
                <button type="button" class="b418-btn b418-btn--ghost" @click="$emit('close')">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M10.5 3.5l-7 7M3.5 3.5l7 7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                    Hide guide
                </button>
            </div>
        </template>

        <FlowDiagram />

        <Alert v-if="setupComplete" variant="success" title="You're all set!">
            Try it: hit the paper-plane button on a connection to send a test with sample data,
            then submit your form for real. Every delivery shows up in the Activity Log.
        </Alert>

        <div class="tg-setup-steps">
            <div
                v-for="(step, index) in steps"
                :key="step.key"
                class="tg-setup-step"
                :class="{ 'tg-setup-step--locked': step.locked }"
            >
                <span
                    class="tg-setup-step__badge"
                    :class="{
                        'tg-setup-step__badge--done': step.done,
                        'tg-setup-step__badge--current': !step.done && step.key === currentStep,
                    }"
                >
                    <svg v-if="step.done" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7.5l3 3 6-6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <template v-else>{{ index + 1 }}</template>
                </span>
                <div class="tg-setup-step__body">
                    <div class="tg-setup-step__title">
                        {{ step.title }}
                        <Badge v-if="step.optional" size="sm">optional</Badge>
                    </div>
                    <p class="tg-setup-step__desc">
                        {{ step.description }}
                        <template v-if="step.key === 'bot'">
                            Get the token from
                            <a href="https://t.me/BotFather" target="_blank" rel="noopener">@BotFather</a>.
                        </template>
                        <template v-else-if="step.locked">
                            Finish steps 1 and 2 first.
                        </template>
                    </p>
                </div>
                <button
                    type="button"
                    class="b418-btn b418-btn--sm tg-setup-step__action"
                    :class="!step.done && step.key === currentStep ? 'b418-btn--primary' : 'b418-btn--secondary'"
                    :disabled="step.locked"
                    @click="go(step)"
                >
                    {{ step.actionLabel }}
                </button>
            </div>
        </div>
    </Card>
</template>

<script setup>
import { Card, Alert, Badge } from '@branch418/shared/components';
import FlowDiagram from './FlowDiagram.vue';
import { useStore } from '../composables/useStore.js';
import { useSetupSteps } from '../composables/useSetupSteps.js';

defineEmits(['close']);

const store = useStore();
const { steps, setupComplete, currentStep, go } = useSetupSteps(store);
</script>
