<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    rows: {
        type: Array,
        default: () => [],
    },
    actors: {
        type: Array,
        default: () => [],
    },
    actions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ action: '', actor_id: null }),
    },
});

const action = ref(props.filters.action || '');
const actorId = ref(props.filters.actor_id || '');

function applyFilters() {
    router.get(
        '/admin/activity-logs',
        {
            action: action.value || undefined,
            actor_id: actorId.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

function clearFilters() {
    action.value = '';
    actorId.value = '';
    router.get('/admin/activity-logs', {}, { preserveState: true, replace: true });
}

function stringifyMeta(meta) {
    if (!meta) return '-';
    return JSON.stringify(meta);
}
</script>

<template>
    <Head title="Activity Logs" />

    <DashboardLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 md:text-3xl">Activity Logs</h1>
                <p class="mt-1 text-sm text-slate-600">Log aktivitas sistem (readonly) untuk audit dan monitoring.</p>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid gap-3 md:grid-cols-[1fr,1fr,auto,auto]">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Action</label>
                        <select
                            v-model="action"
                            class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        >
                            <option value="">Semua action</option>
                            <option v-for="item in actions" :key="item" :value="item">{{ item }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Aktor</label>
                        <select
                            v-model="actorId"
                            class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        >
                            <option value="">Semua aktor</option>
                            <option v-for="item in actors" :key="item.id" :value="item.id">{{ item.name }}</option>
                        </select>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-12 items-center justify-center rounded-xl bg-teal-600 px-5 text-sm font-semibold text-white hover:bg-teal-700"
                        @click="applyFilters"
                    >
                        Terapkan
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-12 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                        @click="clearFilters"
                    >
                        Reset
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Waktu</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Aktor</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Target</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Meta</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="rows.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada activity log.</td>
                            </tr>
                            <tr v-for="item in rows" :key="item.id">
                                <td class="px-4 py-3 text-slate-600">{{ item.created_at }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ item.action }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ item.actor_name || '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ item.target_type || '-' }} #{{ item.target_id || '-' }}</td>
                                <td class="max-w-[320px] px-4 py-3 text-xs text-slate-600">
                                    <pre class="whitespace-pre-wrap break-words">{{ stringifyMeta(item.meta) }}</pre>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ item.ip_address || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
