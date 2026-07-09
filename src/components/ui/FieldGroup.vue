<template>
    <div class="tg-field">
        <label v-if="label">
            {{ label }}
            <span v-if="required" class="tg-field__req">*</span>
        </label>
        <slot />
        <p v-if="hint || $slots.hint" class="tg-field__hint">
            <slot name="hint">{{ hint }}</slot>
        </p>
    </div>
</template>

<script setup>
defineProps({
    label: {
        type: String,
        default: '',
    },
    hint: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
});
</script>
