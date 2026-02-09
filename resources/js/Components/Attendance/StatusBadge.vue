<script setup>
const props = defineProps({
    status: {
        type: String,
        default: 'draft',
    },
});

const statusClass = {
    draft: 'bg-amber-50 text-amber-700 ring-amber-200',
    open: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    closed: 'bg-slate-100 text-slate-700 ring-slate-200',
};

const label = {
    draft: 'Draft',
    open: 'Open',
    closed: 'Closed',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1"
        :class="statusClass[props.status] ?? statusClass.draft"
    >
        {{ label[props.status] ?? props.status }}
    </span>
</template>
