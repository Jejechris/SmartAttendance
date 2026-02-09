<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
});
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ title }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ value }}</p>
    </div>
</template>
