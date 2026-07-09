<template>
    <button
        type="button"
        class="tg-toggle"
        :class="{ 'tg-toggle--on': modelValue }"
        role="switch"
        :aria-checked="modelValue ? 'true' : 'false'"
        :title="modelValue ? 'Enabled' : 'Disabled'"
        @click="$emit('update:modelValue', !modelValue)"
    >
        <span class="tg-toggle__knob" />
    </button>
</template>

<script setup>
defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue']);
</script>
