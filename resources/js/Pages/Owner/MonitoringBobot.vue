<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    ternaks: { type: Array, default: () => [] },
    performa_kandang: { type: Array, default: () => [] },
    performa_ras: { type: Array, default: () => [] },
    top_performer: { type: Array, default: () => [] }
});

const user = computed(() => usePage().props.auth.user);
const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);

const totalBobotTerkumpul = computed(() => {
    return props.ternaks.reduce((sum, t) => sum + (parseFloat(t.berat_kg) || 0), 0).toFixed(1);
});

const rataRataGain = computed(() => {
    if (props.ternaks.length === 0) return '0.00';
    const sum = props.ternaks.reduce((s, t) => s + (parseFloat(t.selisih_kg) || 0), 0);
    return (sum / props.ternaks.length).toFixed(2);
});

const rataRataBobot = computed(() => {
    if (props.ternaks.length === 0) return '0.0';
    const sum = props.ternaks.reduce((s, t) => s + (parseFloat(t.berat_kg) || 0), 0);
    return (sum / props.ternaks.length).toFixed(1);
});

const kandangTerbaik = computed(() => {
    if (props.performa_kandang.length === 0) return '-';
    const sorted = [...props.performa_kandang].sort((a, b) => b.avg_gain - a.avg_gain);
    return sorted[0]?.kandang || '-';
});

let pollingTimer = null;
onMounted(() => {
    pollingTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: ['ternaks', 'performa_kandang', 'performa_ras', 'top_performer']
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer);
});
</script>

<template>
    <Head title="Monitoring Pertumbuhan - Owner" />

    <div class="flex h-screen bg-[#f5f5f9] font-sans text-slate-600 overflow-hidden">
        
        <!-- SIDEBAR OWNER -->
        <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl lg:shadow-none lg:border-r border-slate-100 lg:static lg:translate-x-0 transition-transform duration-300 flex flex-col">
            <div class="h-20 flex items-center px-6 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white font-bold text-xl mr-3 shadow-md shadow-emerald-200">
                    O
                </div>
                <span class="text-xl font-bold text-slate-800 tracking-tight">Kandang Pintar</span>
            </div>

            <div class="flex-1 overflow-y-auto py-5 px-3 space-y-1">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3">Ringkasan Eksekutif</div>
                <Link :href="route('dashboard')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Kinerja Finansial
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Aset & Pertumbuhan</div>
                <Link :href="route('owner.monitoring')" class="flex items-center px-3.5 py-2.5 bg-emerald-50 text-emerald-700 rounded-xl font-semibold transition border border-emerald-100/50">
                    <svg class="w-5 h-5 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                    Monitoring Bobot
                </Link>
                <Link :href="route('owner.breeding')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Silsilah & Breeding
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Komersial & Kas</div>
                <Link :href="route('owner.keuangan')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Buku Kas Kandang
                </Link>
                <Link :href="route('owner.panen')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    Proyeksi Panen
                </Link>
                <Link :href="route('owner.terjual')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Kambing Terjual
                </Link>
            </div>

            <div class="p-4 border-t border-slate-100 text-center mt-auto">
                <p class="text-xs font-semibold text-slate-400">&copy; 2026 JJFarm.</p>
                <p class="text-[10px] text-slate-400">Executive Portal</p>
            </div>
        </aside>

        <!-- MAIN AREA -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <nav class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.06)] mx-4 lg:mx-6 mt-4 px-5 py-3.5 flex justify-between items-center z-40">
                <div class="flex items-center gap-3">
                    <button @click="isSidebarOpen = true" class="lg:hidden text-slate-500 mr-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 leading-tight">Analisis Laju Pertumbuhan Daging (Gain)</h2>
                        <p class="text-xs text-slate-400">Evaluasi akumulasi bobot hidup dan efisiensi pertumbuhan ternak di seluruh kandang</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 relative">
                    <div class="text-right hidden md:block">
                        <div class="text-sm font-bold text-slate-700">{{ user?.name || 'Owner Utama' }}</div>
                        <div class="text-[11px] text-emerald-600 font-semibold uppercase tracking-wider">Pemilik Usaha</div>
                    </div>
                    <button @click="isDropdownOpen = !isDropdownOpen" @blur="setTimeout(() => isDropdownOpen = false, 200)" class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 font-bold uppercase transition hover:ring-2 hover:ring-emerald-300 border-2 border-white shadow-sm flex items-center justify-center">
                        {{ user?.name?.charAt(0) || 'O' }}
                    </button>
                    <div v-show="isDropdownOpen" class="absolute right-0 top-12 w-48 bg-white rounded-xl shadow-xl border border-slate-100 py-1.5 z-50">
                        <Link :href="route('profile.edit')" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-emerald-600 transition">Profil Akun</Link>
                        <Link :href="route('logout')" method="post" as="button" class="w-full text-left block px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 transition font-medium">Logout Keluar</Link>
                    </div>
                </div>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20 space-y-6">
                
                <!-- ROW 1: KARTU METRIK -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <div class="bg-gradient-to-br from-emerald-50/70 via-white to-white rounded-2xl p-5 border border-emerald-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Total Massa Daging</span>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ totalBobotTerkumpul }} <span class="text-xs font-normal text-slate-400">Kg</span></h3>
                            <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">Akumulasi {{ ternaks.length }} Ekor Aktif</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-teal-50/70 via-white to-white rounded-2xl p-5 border border-teal-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-teal-700 uppercase tracking-wider">Laju Tumbuh (Avg Gain)</span>
                            <h3 class="text-2xl font-black text-teal-700 mt-1">+{{ rataRataGain }} <span class="text-xs font-normal text-slate-400">Kg/Bln</span></h3>
                            <p class="text-[11px] text-teal-600 font-semibold mt-0.5">Kenaikan bobot rata-rata</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-amber-50/70 via-white to-white rounded-2xl p-5 border border-amber-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-amber-700 uppercase tracking-wider">Rata-rata Bobot</span>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ rataRataBobot }} <span class="text-xs font-normal text-slate-400">Kg/Ekor</span></h3>
                            <p class="text-[11px] text-amber-600 font-semibold mt-0.5">Kematangan populasi</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-indigo-50/70 via-white to-white rounded-2xl p-5 border border-indigo-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Kandang Paling Efisien</span>
                            <h3 class="text-2xl font-black text-indigo-700 mt-1 truncate max-w-[150px]">{{ kandangTerbaik }}</h3>
                            <p class="text-[11px] text-indigo-500 font-semibold mt-0.5">Laju tumbuh tertinggi</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                    </div>

                </div>

                <!-- ROW 2: EFISIENSI KANDANG & PERFORMA RAS -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h4 class="font-bold text-slate-800">Efisiensi Pertumbuhan per Kandang</h4>
                                <p class="text-xs text-slate-400 mt-0.5">Komparasi laju bobot antar lokasi</p>
                            </div>
                            <span class="text-xs bg-slate-100 px-2.5 py-1 rounded-lg text-slate-500 font-medium">Lokasi</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="text-slate-400 text-xs uppercase border-b border-slate-100">
                                    <tr>
                                        <th class="py-3 font-semibold">Nama Kandang</th>
                                        <th class="py-3 font-semibold">Populasi</th>
                                        <th class="py-3 font-semibold">Rata-rata Bobot</th>
                                        <th class="py-3 font-semibold text-right">Laju Tumbuh</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="(k, idx) in performa_kandang" :key="idx" class="hover:bg-slate-50/60">
                                        <td class="py-3.5 font-bold text-slate-700">{{ k.kandang }}</td>
                                        <td class="py-3.5 text-slate-500">{{ k.total_ekor }} Ekor</td>
                                        <td class="py-3.5 font-semibold text-slate-700">{{ k.avg_berat }} Kg</td>
                                        <td class="py-3.5 text-right">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">
                                                +{{ k.avg_gain }} Kg
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="performa_kandang.length === 0">
                                        <td colspan="4" class="py-6 text-center text-slate-400 text-xs">Belum ada data kandang.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h4 class="font-bold text-slate-800">Kinerja Ras Genetik</h4>
                                <p class="text-xs text-slate-400 mt-0.5">Potensi kenaikan bobot per jenis ternak</p>
                            </div>
                            <span class="text-xs bg-slate-100 px-2.5 py-1 rounded-lg text-slate-500 font-medium">Genetika</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="text-slate-400 text-xs uppercase border-b border-slate-100">
                                    <tr>
                                        <th class="py-3 font-semibold">Jenis Ras</th>
                                        <th class="py-3 font-semibold">Populasi</th>
                                        <th class="py-3 font-semibold">Bobot Maksimal</th>
                                        <th class="py-3 font-semibold text-right">Avg Gain</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="(r, idx) in performa_ras" :key="idx" class="hover:bg-slate-50/60">
                                        <td class="py-3.5 font-bold text-slate-700">{{ r.ras }}</td>
                                        <td class="py-3.5 text-slate-500">{{ r.total_ekor }} Ekor</td>
                                        <td class="py-3.5 font-semibold text-slate-700">{{ r.bobot_tertinggi }} Kg</td>
                                        <td class="py-3.5 text-right">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-600 border border-indigo-100">
                                                +{{ r.avg_gain }} Kg
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="performa_ras.length === 0">
                                        <td colspan="4" class="py-6 text-center text-slate-400 text-xs">Belum ada data ras.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- ROW 3: TOP 5 TERNAK DENGAN KENAIKAN BOBOT TERBAIK -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h4 class="font-bold text-slate-800 text-base">Top 5 Ternak dengan Pertumbuhan Tertinggi</h4>
                            <p class="text-xs text-slate-400">Ternak dengan konversi bobot paling optimal untuk diproyeksikan sebagai bibit unggul atau panen</p>
                        </div>
                        <span class="text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1 rounded-full">Top Gainers</span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 text-xs uppercase bg-slate-50/50">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Peringkat</th>
                                    <th class="px-6 py-3 font-semibold">Tag RFID</th>
                                    <th class="px-6 py-3 font-semibold">Ras & Kandang</th>
                                    <th class="px-6 py-3 font-semibold">Bobot Terkini</th>
                                    <th class="px-6 py-3 font-semibold">Kenaikan Bobot (Gain)</th>
                                    <th class="px-6 py-3 font-semibold">Program</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(t, idx) in top_performer" :key="t.id" class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="w-7 h-7 rounded-full flex items-center justify-center font-black text-xs"
                                              :class="idx === 0 ? 'bg-amber-100 text-amber-700' : (idx === 1 ? 'bg-slate-200 text-slate-700' : (idx === 2 ? 'bg-amber-50 text-amber-800' : 'bg-slate-50 text-slate-500'))">
                                            #{{ idx + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-bold font-mono text-slate-800">{{ t.rfid_tag }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-700">{{ t.ras }}</div>
                                        <div class="text-xs text-slate-400">{{ t.kandang }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-black text-slate-800">{{ t.berat_kg }} Kg</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-100 text-emerald-700 border border-emerald-200">
                                            +{{ t.selisih_kg }} Kg
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600">
                                            {{ t.program }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="top_performer.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-slate-400 text-xs">Belum ada data performa ternak.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>