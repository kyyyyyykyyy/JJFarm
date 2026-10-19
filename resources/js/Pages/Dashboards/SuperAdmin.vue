<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    ternaks: { type: Array, default: () => [] },
    total_users: { type: Number, default: 0 }, 
    total_logs: { type: Number, default: 0 } // Menangkap total log dari controller
});

const user = computed(() => usePage().props.auth.user);

const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);

// Kalkulasi Data Otomatis & Dinamis
const totalUsers = computed(() => props.total_users); 
const totalKeseluruhanData = computed(() => props.ternaks.length);
const dataTerbaruMasuk = computed(() => props.ternaks.slice(0, 5).length); 
const logActivityTerbaru = computed(() => props.total_logs); // SUDAH DINAMIS BRO
</script>

<template>
    <Head title="System Control - Super Admin" />

    <div class="flex h-screen bg-[#f5f5f9] font-sans text-slate-600 overflow-hidden">
        
        <!-- SIDEBAR -->
        <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl lg:shadow-none lg:border-r border-slate-100 lg:static lg:translate-x-0 transition-transform duration-300 flex flex-col">
            <div class="h-20 flex items-center px-6 border-b border-transparent">
                <div class="w-8 h-8 rounded bg-indigo-600 flex items-center justify-center text-white font-bold text-xl mr-3 shadow-md shadow-indigo-200">S</div>
                <span class="text-xl font-bold text-slate-700 tracking-tight">Kandang Pintar</span>
            </div>

            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-4">Menu Utama</div>
                
                <Link :href="route('dashboard')" class="flex items-center px-3 py-2.5 bg-indigo-50 text-indigo-600 rounded-lg font-medium transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </Link>
                <Link :href="route('superadmin.users')" class="flex items-center px-3 py-2.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700 rounded-lg transition font-medium">
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
                <p class="text-xs font-semibold text-slate-400">&copy; 2026 JJFarm.</p>
                <p class="text-[10px] text-slate-400">All rights reserved.</p>
            </div>
        </aside>

        <!-- MAIN AREA -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <nav class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] mx-4 lg:mx-6 mt-4 px-4 py-3 flex justify-between items-center z-40">
                <div class="flex items-center">
                    <button @click="isSidebarOpen = true" class="lg:hidden text-slate-500 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="text-lg font-bold text-slate-700 hidden sm:block">Dashboard System</h2>
                </div>
                <div class="flex items-center gap-4 relative">
                    <button @click="isDropdownOpen = !isDropdownOpen" @blur="setTimeout(() => isDropdownOpen = false, 200)" class="w-8 h-8 rounded-full bg-indigo-100 border-2 border-white shadow-sm flex items-center justify-center text-indigo-600 font-bold uppercase transition hover:ring-2 hover:ring-indigo-200">
                        {{ user?.name.charAt(0) || 'S' }}
                    </button>
                    <div v-show="isDropdownOpen" class="absolute right-0 top-10 w-48 bg-white rounded-lg shadow-lg py-1 z-50">
                        <div class="px-4 py-2 border-b border-slate-50">
                            <p class="text-sm font-bold text-slate-700">{{ user?.name || 'Super Admin' }}</p>
                            <p class="text-xs text-slate-400 capitalize">{{ user?.role?.replace('_', ' ') || 'Administrator' }}</p>
                        </div>
                        <Link :href="route('profile.edit')" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition">Profil & Keamanan</Link>
                        <Link :href="route('logout')" method="post" as="button" class="w-full text-left block px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 transition">Logout Keluar</Link>
                    </div>
                </div>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20">
                <div class="mb-6 bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] p-6 relative overflow-hidden flex items-center">
                    <div class="w-full lg:w-3/4 relative z-10">
                        <h3 class="text-2xl font-bold text-indigo-600 mb-2">Selamat Datang, {{ user?.name || 'Super Admin' }}! 🎉</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">
                            Sistem penerimaan data dari gateway ESP32 berjalan normal. Anda memantau lalu lintas API dan aktivitas user di seluruh area operasional farm.
                        </p>
                    </div>
                    <div class="absolute bottom-0 right-0 w-32 lg:w-48 opacity-90 pointer-events-none translate-x-4 lg:translate-x-0 hidden sm:block">
                        <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#E0E7FF" d="M45.7,-74.8C58.8,-66.5,68.9,-53.4,77.7,-39.4C86.5,-25.3,94,-10.3,92.5,4C91,18.4,80.5,32,71.2,46.1C61.8,60.1,53.5,74.5,41,81.4C28.4,88.2,11.5,87.6,-3.6,83.1C-18.7,78.6,-32.1,70.2,-44.6,60.8C-57.1,51.4,-68.8,40.9,-75.6,27.5C-82.5,14,-84.6,-2.5,-81.4,-17.9C-78.2,-33.4,-69.6,-47.8,-57,-56.3C-44.4,-64.8,-27.7,-67.4,-11.9,-65.8C3.9,-64.2,19.7,-58.5,32.6,-71.8Z" transform="translate(100 100)" />
                            <text x="100" y="120" font-size="60" text-anchor="middle" dominant-baseline="middle">💻</text>
                        </svg>
                    </div>
                </div>

                <!-- KARTU METRIK CLICKABLE -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <Link :href="route('superadmin.users')" class="block bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] p-5 hover:-translate-y-1 hover:shadow-lg hover:border-indigo-100 border border-transparent transition-all duration-300 cursor-pointer">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-slate-500">Total User</h4>
                                <h2 class="text-2xl font-bold text-slate-700">{{ totalUsers }} <span class="text-xs font-normal text-slate-400">Akun</span></h2>
                            </div>
                        </div>
                    </Link>

                    <Link :href="route('superadmin.backup')" class="block bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] p-5 hover:-translate-y-1 hover:shadow-lg hover:border-indigo-100 border border-transparent transition-all duration-300 cursor-pointer">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-slate-500">Total Keseluruhan</h4>
                                <h2 class="text-2xl font-bold text-slate-700">{{ totalKeseluruhanData }} <span class="text-xs font-normal text-slate-400">Data</span></h2>
                            </div>
                        </div>
                    </Link>

                    <Link :href="route('superadmin.backup')" class="block bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] p-5 hover:-translate-y-1 hover:shadow-lg hover:border-indigo-100 border border-transparent transition-all duration-300 cursor-pointer">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-sky-50 text-sky-500 flex items-center justify-center mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-slate-500">Data Terbaru</h4>
                                <h2 class="text-2xl font-bold text-slate-700">{{ dataTerbaruMasuk }} <span class="text-xs font-normal text-slate-400">Masuk</span></h2>
                            </div>
                        </div>
                    </Link>

                    <Link :href="route('superadmin.logs')" class="block bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] p-5 hover:-translate-y-1 hover:shadow-lg hover:border-indigo-100 border border-transparent transition-all duration-300 cursor-pointer">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-slate-500">Log Activity</h4>
                                <h2 class="text-2xl font-bold text-slate-700">{{ logActivityTerbaru }} <span class="text-xs font-normal text-slate-400">Tercatat</span></h2>
                            </div>
                        </div>
                    </Link>
                </div>

                <div class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
                        <h4 class="font-bold text-slate-700 text-lg">Log Lalu Lintas API (Incoming Data)</h4>
                        <span class="text-xs font-bold bg-slate-100 text-slate-500 px-3 py-1 rounded">System Logs</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 border-b border-slate-100 text-xs uppercase bg-white">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Waktu Masuk</th>
                                    <th class="px-6 py-4 font-semibold">Sumber Gateway</th>
                                    <th class="px-6 py-4 font-semibold">Payload Data</th>
                                    <th class="px-6 py-4 font-semibold">Protokol</th>
                                    <th class="px-6 py-4 font-semibold">Status API</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="t in ternaks" :key="t.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-700">{{ t.tgl_pendataan }}</div>
                                        <div class="text-slate-400 text-xs mt-1">Sistem Auto-Sync</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-indigo-600">Gateway ESP32</div>
                                        <div class="text-slate-500 text-xs mt-1 truncate">Endpoint: {{ t.kandang }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-slate-700 font-mono text-xs">RFID: {{ t.rfid_tag }}</div>
                                        <div class="text-slate-400 text-xs mt-1">Size: ~124 bytes</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-bold font-mono">HTTP POST</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-emerald-50 text-emerald-600">200 OK</span>
                                    </td>
                                </tr>
                                <tr v-if="ternaks.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">Belum ada aktivitas transmisi data dari perangkat luar.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>