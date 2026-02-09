<script setup>
defineProps({
    type: {
        type: String,
        default: 'success',
    },
    message: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div
        v-if="message"
        :class="[
            'rounded-xl border px-4 py-3 text-sm font-medium',
            type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700',
        ]"
    >
        {{ message }}
    </div>
</template>
