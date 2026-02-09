<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AppFlash from '@/Components/AppFlash.vue';

const props = defineProps({
    rows: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const search = ref('');
const processingId = ref(null);

const flashSuccess = computed(() => page.props.flash?.success ?? '');
const flashError = computed(() => page.props.flash?.error ?? '');

const filteredRows = computed(() => {
    const keyword = search.value.toLowerCase().trim();
    if (!keyword) return props.rows;

    return props.rows.filter((item) => {
        return (
            (item.name || '').toLowerCase().includes(keyword) ||
            (item.email || '').toLowerCase().includes(keyword) ||
            (item.role || '').toLowerCase().includes(keyword)
        );
    });
});

function roleLabel(role) {
    if (role === 'school_admin') return 'School Admin';
    if (role === 'teacher') return 'Guru';
    if (role === 'student') return 'Siswa';
    return role;
}

function updateRole(userId, role) {
    processingId.value = userId;
    router.post(
        `/admin/settings/users/${userId}/role`,
        { role },
        {
            preserveScroll: true,
            onFinish: () => {
                processingId.value = null;
            },
        }
    );
}
</script>

<template>
    <Head title="Role Management" />

    <DashboardLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 md:text-3xl">Role Management</h1>
                <p class="mt-1 text-sm text-slate-600">Atur hak akses user sekolah dengan aman dan sederhana.</p>
            </div>

            <AppFlash :message="flashSuccess" type="success" />
            <AppFlash :message="flashError" type="error" />

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <label class="mb-1 block text-sm font-medium text-slate-700">Cari user</label>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Nama / email / role"
                    class="h-12 w-full max-w-md rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                />
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Email</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Role</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Bergabung</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Ubah Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredRows.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Data user tidak ditemukan.</td>
                            </tr>
                            <tr v-for="item in filteredRows" :key="item.id">
                                <td class="px-4 py-3 font-medium text-slate-800">{{ item.name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ item.email }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="{
                                            'bg-indigo-100 text-indigo-700': item.role === 'school_admin',
                                            'bg-teal-100 text-teal-700': item.role === 'teacher',
                                            'bg-slate-200 text-slate-700': item.role === 'student',
                                        }"
                                    >
                                        {{ roleLabel(item.role) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ item.created_at }}</td>
                                <td class="px-4 py-3">
                                    <select
                                        :value="item.role"
                                        class="h-10 rounded-lg border border-slate-300 px-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                                        :disabled="processingId === item.id"
                                        @change="updateRole(item.id, $event.target.value)"
                                    >
                                        <option v-for="role in roles" :key="role" :value="role">{{ roleLabel(role) }}</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
