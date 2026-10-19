<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Pengaturan Akun - JJFarm" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 tracking-tight">Pengaturan Profil & Keamanan</h2>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Kelola informasi identitas petugas dan kredensial akses sistem</p>
                </div>
                <div class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                    <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Akses Terverifikasi</span>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- KARTU SUMMARY PROFIL ATAS -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-6 relative overflow-hidden">
                    <div class="w-20 h-20 rounded-2xl bg-emerald-900 text-white flex items-center justify-center font-black text-2xl shadow-lg border border-emerald-700 shrink-0">
                        {{ user.name ? user.name.substring(0, 2).toUpperCase() : 'JJ' }}
                    </div>

                    <div class="space-y-1.5 text-center sm:text-left flex-1">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ user.name }}</h3>
                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-md text-[11px] font-black tracking-wider uppercase bg-emerald-100 text-emerald-800 self-center sm:self-auto">
                                Petugas Terdaftar
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 font-medium font-mono">{{ user.email }}</p>
                        <div class="pt-2 flex flex-wrap items-center justify-center sm:justify-start gap-4 text-xs text-slate-500">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                Keamanan Akun Aktif
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                JJFarm Operating Core
                            </span>
                        </div>
                    </div>
                </div>

                <!-- FORM 1: INFORMASI IDENTITAS AKUN -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <!-- FORM 2: PEMBARUAN KATA SANDI -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm">
                    <UpdatePasswordForm />
                </div>

                <!-- FORM 3: HAPUS AKUN (OPSIONAL / JIKA ADA) -->
                <div class="bg-rose-50/50 rounded-3xl p-6 sm:p-8 border border-rose-200 shadow-sm">
                    <DeleteUserForm />
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>