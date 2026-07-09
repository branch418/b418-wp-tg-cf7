<template>
    <Modal :model-value="modelValue" :title="title" size="sm" @update:model-value="$emit('update:modelValue', $event)">
        <p style="margin: 0;">{{ message }}</p>
        <template #footer="{ close }">
            <button type="button" class="b418-btn b418-btn--secondary" @click="close">
                Cancel
            </button>
            <button
                type="button"
                class="b418-btn"
                :class="danger ? 'b418-btn--danger' : 'b418-btn--primary'"
                @click="confirm"
            >
                {{ confirmLabel }}
            </button>
        </template>
    </Modal>
</template>

<script setup>
import { Modal } from '@branch418/shared/components';

defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Are you sure?',
    },
    message: {
        type: String,
        default: '',
    },
    confirmLabel: {
        type: String,
        default: 'Delete',
    },
    danger: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue', 'confirm']);

function confirm() {
    emit('confirm');
    emit('update:modelValue', false);
}
</script>
