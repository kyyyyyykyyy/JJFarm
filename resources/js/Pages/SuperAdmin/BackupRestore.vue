<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    dataLogs: { type: Array, default: () => [] }
});

const currentUser = computed(() => usePage().props.auth.user);
const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);
</script>

<template>
    <Head title="Backup & Restore - Super Admin" />

    <div class="flex h-screen bg-[#f5f5f9] font-sans text-slate-600 overflow-hidden">
        
        <!-- SIDEBAR -->
        <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl lg:shadow-none lg:border-r border-slate-100 lg:static lg:translate-x-0 transition-transform duration-300 flex flex-col">
            <div class="h-20 flex items-center px-6 border-b border-transparent">
                <div class="w-8 h-8 rounded bg-indigo-600 flex items-center justify-center text-white font-bold text-xl mr-3 shadow-md shadow-indigo-200">S</div>
                <span class="text-xl font-bold text-slate-700 tracking-tight">Kandang Pintar</span>
            </div>

            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-4">Menu Utama</div>
                <Link :href="route('dashboard')" class="flex items-center px-3 py-2.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700 rounded-lg transition font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg> Dashboard
                </Link>
                <Link :href="route('superadmin.users')" class="flex items-center px-3 py-2.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700 rounded-lg transition font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg> User Management
                </Link>
                
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-6">Sistem & Keamanan</div>
                
                <!-- Menu Backup Aktif -->
                <Link :href="route('superadmin.backup')" class="flex items-center px-3 py-2.5 bg-indigo-50 text-indigo-600 rounded-lg font-medium transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg> Backup Data
                </Link>
                <Link :href="route('superadmin.logs')" class="flex items-center px-3 py-2.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700 rounded-lg transition font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg> Security Logs
                </Link>
            </div>
            <div class="p-4 border-t border-slate-100 text-center mt-auto">
                <p class="text-xs font-semibold text-slate-400">&copy; 2026 JJFarm.</p>
            </div>
        </aside>

        <!-- MAIN AREA -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <nav class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] mx-4 lg:mx-6 mt-4 px-4 py-3 flex justify-between items-center z-40">
                <div class="flex items-center">
                    <button @click="isSidebarOpen = true" class="lg:hidden text-slate-500 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="text-lg font-bold text-slate-700 hidden sm:block">Backup & Ekstraksi Data</h2>
                </div>
                <div class="flex items-center gap-4 relative">
                    <button @click="isDropdownOpen = !isDropdownOpen" @blur="setTimeout(() => isDropdownOpen = false, 200)" class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold uppercase transition hover:ring-2 hover:ring-indigo-200">
                        {{ currentUser?.name.charAt(0) || 'S' }}
                    </button>
                    <div v-show="isDropdownOpen" class="absolute right-0 top-10 w-48 bg-white rounded-lg shadow-lg py-1 z-50">
                        <div class="px-4 py-2 border-b border-slate-50">
                            <p class="text-sm font-bold text-slate-700">{{ currentUser?.name }}</p>
                            <p class="text-xs text-slate-400 capitalize">{{ currentUser?.role?.replace('_', ' ') }}</p>
                        </div>
                        <Link :href="route('logout')" method="post" as="button" class="w-full text-left block px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 transition">Logout Keluar</Link>
                    </div>
                </div>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20">
                
                <!-- CARD DOWNLOAD DATA -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Harian -->
                    <div class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] p-5 border-t-4 border-emerald-500 flex flex-col justify-between">
                        <div>
                            <h4 class="font-bold text-slate-700 mb-1">Backup Harian</h4>
                            <p class="text-xs text-slate-500 mb-4">Unduh data yang masuk hari ini dari semua gateway.</p>
                        </div>
                        <a :href="route('superadmin.backup.export', { period: 'harian' })" class="w-full text-center px-4 py-2 text-sm font-bold text-emerald-600 bg-emerald-50 hover:bg-emerald-500 hover:text-white rounded-lg transition">Unduh CSV</a>
                    </div>
                    
                    <!-- Mingguan -->
                    <div class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] p-5 border-t-4 border-sky-500 flex flex-col justify-between">
                        <div>
                            <h4 class="font-bold text-slate-700 mb-1">Backup Mingguan</h4>
                            <p class="text-xs text-slate-500 mb-4">Unduh akumulasi data selama 7 hari terakhir.</p>
                        </div>
                        <a :href="route('superadmin.backup.export', { period: 'mingguan' })" class="w-full text-center px-4 py-2 text-sm font-bold text-sky-600 bg-sky-50 hover:bg-sky-500 hover:text-white rounded-lg transition">Unduh CSV</a>
                    </div>

                    <!-- Bulanan -->
                    <div class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] p-5 border-t-4 border-indigo-500 flex flex-col justify-between">
                        <div>
                            <h4 class="font-bold text-slate-700 mb-1">Backup Bulanan</h4>
                            <p class="text-xs text-slate-500 mb-4">Unduh total pencatatan dan evaluasi AI bulan ini.</p>
                        </div>
                        <a :href="route('superadmin.backup.export', { period: 'bulanan' })" class="w-full text-center px-4 py-2 text-sm font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-500 hover:text-white rounded-lg transition">Unduh CSV</a>
                    </div>

                    <!-- Tahunan -->
                    <div class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] p-5 border-t-4 border-amber-500 flex flex-col justify-between">
                        <div>
                            <h4 class="font-bold text-slate-700 mb-1">Backup Tahunan</h4>
                            <p class="text-xs text-slate-500 mb-4">Master Data penuh untuk arsip tahun ini.</p>
                        </div>
                        <a :href="route('superadmin.backup.export', { period: 'tahunan' })" class="w-full text-center px-4 py-2 text-sm font-bold text-amber-600 bg-amber-50 hover:bg-amber-500 hover:text-white rounded-lg transition">Unduh CSV</a>
                    </div>
                </div>

                <!-- TABEL DATA MASUK -->
                <div class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.08)] overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100">
                        <h4 class="font-bold text-slate-700 text-lg">Log Historis Sinkronisasi Data</h4>
                        <p class="text-xs text-slate-400 mt-1">Menampilkan 50 antrean data terbaru yang ditarik dari ESP32.</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 border-b border-slate-100 text-xs uppercase bg-white">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Tipe Transaksi</th>
                                    <th class="px-6 py-4 font-semibold">RFID Tag</th>
                                    <th class="px-6 py-4 font-semibold">Data Masuk (Payload)</th>
                                    <th class="px-6 py-4 font-semibold">Diagnosis AI</th>
                                    <th class="px-6 py-4 font-semibold">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="log in dataLogs" :key="log.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="bg-indigo-50 text-indigo-600 px-2 py-1 rounded text-xs font-bold">Data Masuk (IN)</span>
                                    </td>
                                    <td class="px-6 py-4 font-mono font-bold text-slate-700">{{ log.rfid_tag }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-slate-700">Berat: {{ log.berat_terakhir }} Kg</div>
                                        <div class="text-slate-400 text-xs">Lokasi: {{ log.kandang }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold" 
                                              :class="log.prediksi_ai === 'SEHAT NORMAL' ? 'text-emerald-600' : 'text-rose-600'">
                                            {{ log.prediksi_ai || 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 font-mono text-xs">
                                        {{ new Date(log.updated_at).toLocaleString('id-ID') }}
                                    </td>
                                </tr>
                                <tr v-if="dataLogs.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">Belum ada data terekam dari hardware.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>