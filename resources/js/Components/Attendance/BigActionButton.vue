<script setup>
defineProps({
    variant: {
        type: String,
        default: 'primary',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <button
        :disabled="loading || disabled"
        :class="[
            'inline-flex h-12 items-center justify-center rounded-xl px-5 text-sm font-semibold transition focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:opacity-60',
            variant === 'primary' ? 'bg-teal-600 text-white hover:bg-teal-700 focus:ring-teal-200' : '',
            variant === 'danger' ? 'bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-200' : '',
            variant === 'secondary' ? 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 focus:ring-slate-200' : '',
        ]"
    >
        <svg
            v-if="loading"
            class="mr-2 h-4 w-4 animate-spin"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4" />
            <path d="M22 12A10 10 0 0012 2" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
        </svg>
        <slot />
    </button>
</template>
