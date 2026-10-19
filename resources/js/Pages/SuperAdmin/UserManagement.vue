<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';

const props = defineProps({
    users: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) }
});

const currentUser = computed(() => usePage().props.auth.user);
const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);

const isUserModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const modalMode = ref('add'); 
const selectedUser = ref(null);

const form = useForm({
    id: null,
    name: '',
    email: '',
    role: 'anak_kandang',
    password: '',
    reset_password: false 
});

const openAddModal = () => {
    modalMode.value = 'add';
    form.reset();
    form.clearErrors();
    form.reset_password = false;
    isUserModalOpen.value = true;
};

const openEditModal = (targetUser) => {
    modalMode.value = 'edit';
    form.reset();
    form.clearErrors();
    form.id = targetUser.id;
    form.name = targetUser.name;
    form.email = targetUser.email;
    form.role = targetUser.role;
    form.password = '';
    form.reset_password = false; 
    isUserModalOpen.value = true;
};

const openDeleteModal = (targetUser) => {
    selectedUser.value = targetUser;
    isDeleteModalOpen.value = true;
};

const submitUserForm = () => {
    if (modalMode.value === 'add') {
        form.post(route('superadmin.users.store'), {
            onSuccess: () => {
                isUserModalOpen.value = false;
                form.reset();
            }
        });
    } else {
        form.put(route('superadmin.users.update', form.id), {
            onSuccess: () => {
                isUserModalOpen.value = false;
                form.reset();
            }
        });
    }
};

const confirmDelete = () => {
    if (!selectedUser.value) return;
    form.delete(route('superadmin.users.destroy', selectedUser.value.id), {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            selectedUser.value = null;
        }
    });
};
</script>

<template>
    <Head title="User Management - Super Admin" />

    <div class="flex h-screen bg-[#f5f5f9] font-sans text-slate-600 overflow-hidden">
        
        <!-- SIDEBAR -->
        <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl lg:shadow-none lg:border-r border-slate-100 lg:static lg:translate-x-0 transition-transform duration-300 flex flex-col">
            <div class="h-20 flex items-center px-6 border-b border-transparent">
                <div class="w-8 h-8 rounded bg-indigo-600 flex items-center justify-center text-white font-bold text-xl mr-3 shadow-md shadow-indigo-200">S</div>
                <span class="text-xl font-bold text-slate-700 tracking-tight">Kandang Pintar</span>
                <button @click="isSidebarOpen = false" class="lg:hidden ml-auto text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-4">Menu Utama</div>
                
                <Link :href="route('dashboard')" class="flex items-center px-3 py-2.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700 rounded-lg transition font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </Link>
                
                <!-- Menu Aktif -->
                <Link href="#" class="flex items-center px-3 py-2.5 bg-indigo-50 text-indigo-600 rounded-lg font-medium transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    User Management
                </Link>
                
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-6">Sistem & Keamanan</div>
                
                <Link :href="route('superadmin.backup')" class="flex items-center px-3 py-2.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700 rounded-lg transition font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    Backup & Restore
                </Link>
                
                <Link :href="route('superadmin.logs')" class="flex items-center px-3 py-2.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700 rounded-lg transition font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Security Logs
                </Link>
            </div>
            
            <div class="p-4 border-t border-slate-100 text-center mt-auto">
                <p class="text-xs font-semibold text-slate-400">&copy; 2026 Kandang Pintar.</p>
            </div>
        </aside>

        <!-- MAIN AREA -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <nav class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] mx-4 lg:mx-6 mt-4 px-4 py-3 flex justify-between items-center z-40">
                <div class="flex items-center">
                    <button @click="isSidebarOpen = true" class="lg:hidden text-slate-500 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="text-lg font-bold text-slate-700 hidden sm:block">Manajemen Akun & Akses</h2>
                </div>
                
                <div class="flex items-center gap-4 relative">
                    <button @click="isDropdownOpen = !isDropdownOpen" @blur="setTimeout(() => isDropdownOpen = false, 200)" class="w-8 h-8 rounded-full bg-indigo-100 border-2 border-white shadow-sm flex items-center justify-center text-indigo-600 font-bold uppercase transition hover:ring-2 hover:ring-indigo-200">
                        {{ currentUser?.name.charAt(0) || 'S' }}
                    </button>
                    <div v-show="isDropdownOpen" class="absolute right-0 top-10 w-48 bg-white rounded-lg shadow-lg border border-slate-100 py-1 z-50">
                        <div class="px-4 py-2 border-b border-slate-50">
                            <p class="text-sm font-bold text-slate-700">{{ currentUser?.name || 'Super Admin' }}</p>
                            <p class="text-xs text-slate-400 capitalize">{{ currentUser?.role?.replace('_', ' ') || 'Administrator' }}</p>
                        </div>
                        <Link :href="route('profile.edit')" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition">Profil & Keamanan</Link>
                        <Link :href="route('logout')" method="post" as="button" class="w-full text-left block px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 transition">Logout Keluar</Link>
                    </div>
                </div>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20">
                <div v-if="props.errors?.message" class="mb-4 bg-rose-50 text-rose-600 px-4 py-3 rounded-lg border border-rose-100 text-sm font-medium">
                    {{ props.errors.message }}
                </div>

                <div class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                        <div>
                            <h4 class="font-bold text-slate-700 text-lg">Daftar Pengguna Sistem</h4>
                            <p class="text-xs text-slate-400 mt-1">Mengontrol hak akses Owner, Admin, dan Anak Kandang.</p>
                        </div>
                        <button @click="openAddModal" class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Tambah User
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 border-b border-slate-100 text-xs uppercase bg-white">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Profil Pengguna</th>
                                    <th class="px-6 py-4 font-semibold">Tingkat Akses (Role)</th>
                                    <th class="px-6 py-4 font-semibold">Status Akun</th>
                                    <th class="px-6 py-4 font-semibold text-right">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 flex items-center">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white mr-3 uppercase shadow-sm"
                                             :class="u.role === 'super_admin' ? 'bg-indigo-500' : (u.role === 'owner' ? 'bg-emerald-500' : 'bg-amber-500')">
                                            {{ u.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-700">{{ u.name }}</div>
                                            <div class="text-slate-400 text-xs mt-0.5">{{ u.email }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold uppercase tracking-wider" 
                                              :class="{
                                                  'bg-indigo-50 text-indigo-600 border border-indigo-100': u.role === 'super_admin',
                                                  'bg-emerald-50 text-emerald-600 border border-emerald-100': u.role === 'owner',
                                                  'bg-amber-50 text-amber-600 border border-amber-100': u.role === 'anak_kandang'
                                              }">
                                            {{ u.role.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                                            <span class="text-slate-600 font-medium">Aktif</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="openEditModal(u)" class="text-slate-400 hover:text-indigo-600 transition p-1" title="Edit Profil/Reset Password">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <button v-if="currentUser.id !== u.id" @click="openDeleteModal(u)" class="text-slate-400 hover:text-rose-600 transition p-1 ml-2" title="Hapus User">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- MODAL TAMBAH / EDIT USER -->
                <div v-if="isUserModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900 bg-opacity-50 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <h3 class="font-bold text-slate-800 text-lg">{{ modalMode === 'add' ? 'Tambah Pengguna Baru' : 'Edit & Reset Akun' }}</h3>
                            <button @click="isUserModalOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <form @submit.prevent="submitUserForm" class="p-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Nama Lengkap</label>
                                    <input v-model="form.name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 outline-none transition" required placeholder="Contoh: Budi Santoso">
                                    <span v-if="form.errors.name" class="text-xs text-rose-500 mt-1 block">{{ form.errors.name }}</span>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Email / Username</label>
                                    <input v-model="form.email" type="email" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 outline-none transition" required placeholder="budi@kandang.com">
                                    <span v-if="form.errors.email" class="text-xs text-rose-500 mt-1 block">{{ form.errors.email }}</span>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Role Akses</label>
                                    <select v-model="form.role" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 outline-none transition">
                                        <option value="super_admin">Super Admin (Akses Penuh)</option>
                                        <option value="owner">Owner (Performa & Keuangan)</option>
                                        <option value="anak_kandang">Anak Kandang (Aksi Medis & Input)</option>
                                    </select>
                                    <span v-if="form.errors.role" class="text-xs text-rose-500 mt-1 block">{{ form.errors.role }}</span>
                                </div>

                                <div v-if="modalMode === 'add'">
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Password</label>
                                    <input v-model="form.password" type="password" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 outline-none transition" required placeholder="Minimal 8 Karakter">
                                    <span v-if="form.errors.password" class="text-xs text-rose-500 mt-1 block">{{ form.errors.password }}</span>
                                </div>

                                <div v-if="modalMode === 'edit'" class="mt-4 p-4 rounded-lg bg-rose-50 border border-rose-100 flex items-start gap-3 transition">
                                    <div class="flex items-center h-5 mt-0.5">
                                        <input id="reset_password" v-model="form.reset_password" type="checkbox" class="w-4 h-4 text-indigo-600 bg-white border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                                    </div>
                                    <div class="text-sm">
                                        <label for="reset_password" class="font-bold text-rose-700 cursor-pointer block">Reset Password ke Default</label>
                                        <p class="text-xs text-rose-600 mt-1 leading-relaxed">
                                            Centang ini jika pengguna lupa sandi. Password akun ini akan otomatis dikembalikan menjadi: <span class="font-mono font-bold bg-white px-1.5 py-0.5 rounded shadow-sm border border-rose-200">password</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-8 flex justify-end gap-3">
                                <button type="button" @click="isUserModalOpen = false" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Batal</button>
                                <button type="submit" :disabled="form.processing" class="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition shadow-sm disabled:opacity-70">
                                    {{ modalMode === 'add' ? 'Simpan Pengguna' : 'Update & Reset' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODAL KONFIRMASI HAPUS -->
                <div v-if="isDeleteModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900 bg-opacity-50 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">Hapus Akun Ini?</h3>
                        <p class="text-sm text-slate-500 mb-6">Akun <span class="font-bold text-slate-700">{{ selectedUser?.name }}</span> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
                        
                        <div class="flex justify-center gap-3">
                            <button @click="isDeleteModalOpen = false" class="w-full px-4 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Batal</button>
                            <button @click="confirmDelete" :disabled="form.processing" class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition shadow-sm disabled:opacity-70">Ya, Hapus!</button>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>