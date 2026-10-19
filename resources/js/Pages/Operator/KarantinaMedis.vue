<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    pasien: { type: Array, default: () => [] },
    semua_ternak: { type: Array, default: () => [] },
    kandangs: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            total_pasien: 0,
            fase_kritis: 0,
            menuju_pulih: 0,
            kapasitas_krt: 15
        })
    }
});

const user = computed(() => usePage().props.auth.user);
const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);

// Filter & Pencarian Pasien
const searchQuery = ref('');
const filterKondisi = ref('All');

const filteredPasien = computed(() => {
    return props.pasien.filter(p => {
        const q = (searchQuery.value || '').toLowerCase();
        const rfid = (p.rfid_tag || '').toLowerCase();
        const ras = (p.ras || '').toLowerCase();
        const gejala = (p.gejala_sakit || '').toLowerCase();
        const matchSearch = rfid.includes(q) || ras.includes(q) || gejala.includes(q);

        let matchKondisi = true;
        if (filterKondisi.value === 'Kritis') {
            matchKondisi = p.persentase_sembuh < 50;
        } else if (filterKondisi.value === 'Pemulihan') {
            matchKondisi = p.persentase_sembuh >= 50;
        }

        return matchSearch && matchKondisi;
    });
});

// Pencarian Cepat Kambing Sehat untuk Dilaporkan Sakit
const queryCariKambing = ref('');
const kandidatSakit = computed(() => {
    if (!queryCariKambing.value || queryCariKambing.value.length < 2) return [];
    const q = queryCariKambing.value.toLowerCase();
    return props.semua_ternak.filter(t => 
        (t.rfid_tag.toLowerCase().includes(q) || t.ras.toLowerCase().includes(q)) &&
        (t.kondisi_kesehatan === 'Sehat' || !t.kondisi_kesehatan)
    ).slice(0, 5);
});

// MODAL 1: LAPOR SAKIT MENDADAK
const isModalLaporOpen = ref(false);
const ternakTerpilihLapor = ref(null);
const formLapor = useForm({
    gejala_sakit: '',
    penanganan_medis: '',
    persentase_sembuh: 30,
    pindah_karantina: true
});

const bukaModalLapor = (t) => {
    ternakTerpilihLapor.value = t;
    formLapor.reset();
    formLapor.persentase_sembuh = 30;
    formLapor.pindah_karantina = true;
    queryCariKambing.value = '';
    isModalLaporOpen.value = true;
};

const submitLapor = () => {
    if (!ternakTerpilihLapor.value) return;
    formLapor.post(route('operator.karantina.lapor', ternakTerpilihLapor.value.id), {
        preserveScroll: true,
        onSuccess: () => isModalLaporOpen.value = false
    });
};

// MODAL 2: UPDATE PROGRES MEDIS
const isModalProgresOpen = ref(false);
const ternakTerpilihProgres = ref(null);
const formProgres = useForm({
    persentase_sembuh: 50,
    penanganan_tambahan: '',
    catatan_evaluasi: ''
});

const bukaModalProgres = (p) => {
    ternakTerpilihProgres.value = p;
    formProgres.persentase_sembuh = p.persentase_sembuh;
    formProgres.penanganan_tambahan = '';
    formProgres.catatan_evaluasi = p.catatan_pakar;
    isModalProgresOpen.value = true;
};

const submitProgres = () => {
    if (!ternakTerpilihProgres.value) return;
    formProgres.put(route('operator.karantina.progres', ternakTerpilihProgres.value.id), {
        preserveScroll: true,
        onSuccess: () => isModalProgresOpen.value = false
    });
};

// MODAL 3: VERIFIKASI SEHAT OLEH PAKAR
const isModalSembuhOpen = ref(false);
const ternakTerpilihSembuh = ref(null);
const formSembuh = useForm({
    kandang_tujuan: 'Blok A',
    program_tujuan: 'Penggemukan',
    catatan_pelepasan: 'Kondisi fisik lincah, nafsu makan normal, bebas gejala sakit.'
});

const bukaModalSembuh = (p) => {
    ternakTerpilihSembuh.value = p;
    formSembuh.kandang_tujuan = p.jenis_kelamin === 'Jantan' ? 'Blok A' : 'Blok B';
    formSembuh.program_tujuan = p.berat_kg >= 30 ? 'Penggemukan' : 'Produksi';
    formSembuh.catatan_pelepasan = 'Lolos evaluasi medis pakar, dinyatakan 100% sehat prima.';
    isModalSembuhOpen.value = true;
};

const submitSembuh = () => {
    if (!ternakTerpilihSembuh.value) return;
    formSembuh.post(route('operator.karantina.sembuh', ternakTerpilihSembuh.value.id), {
        preserveScroll: true,
        onSuccess: () => isModalSembuhOpen.value = false
    });
};

// Auto-Polling Real-time 5 Detik
let pollingTimer = null;
onMounted(() => {
    pollingTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: ['pasien', 'stats', 'semua_ternak']
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer);
});
</script>

<template>
    <Head title="Karantina Medis & Isolasi - Anak Kandang" />

    <div class="flex h-screen bg-[#f5f5f9] font-sans text-slate-600 overflow-hidden">
        
        <!-- SIDEBAR OPERATOR -->
        <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl lg:shadow-none lg:border-r border-slate-100 lg:static lg:translate-x-0 transition-transform duration-300 flex flex-col">
            <div class="h-20 flex items-center px-6 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white font-bold text-xl mr-3 shadow-md shadow-emerald-200">
                    A
                </div>
                <div>
                    <span class="text-base font-bold text-slate-800 block leading-tight">Kandang Pintar</span>
                    <span class="text-[10px] text-emerald-600 font-semibold tracking-wider uppercase">Portal Anak Kandang</span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto py-5 px-3 space-y-1">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3">Ringkasan Tugas</div>
                <Link :href="route('dashboard')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    Pusat Kendali Lapangan
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Data & Inventaris</div>
                <Link :href="route('operator.katalog')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    Katalog Data Ternak
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">SOP Kandang & Program</div>
                <Link :href="route('operator.program')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    Program Pembibitan & Gemuk
                </Link>
                <Link :href="route('operator.kandang')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    Pipeline Siklus Kandang
                </Link>

                <!-- MENU KARANTINA & MEDIS AKTIF -->
                <Link :href="route('operator.karantina')" class="flex items-center px-3.5 py-2.5 bg-emerald-50 text-emerald-700 rounded-xl font-semibold transition border border-emerald-100/50">
                    <svg class="w-5 h-5 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    Karantina & Medis
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Komersial & Arsip</div>
                <Link :href="route('operator.kasir')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    Kasir Penjualan Lapangan
                </Link>
                <Link :href="route('operator.arsip')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                    Gudang Data Arsip
                </Link>
            </div>

            <div class="p-4 border-t border-slate-100 text-center mt-auto">
                <p class="text-xs font-semibold text-slate-400">&copy; 2026 JJFarm.</p>
                <p class="text-[10px] text-slate-400">Operator Field System</p>
            </div>
        </aside>

        <!-- MAIN AREA -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <nav class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.06)] mx-4 lg:mx-6 mt-4 px-5 py-3.5 flex justify-between items-center z-40">
                <div class="flex items-center gap-3">
                    <button @click="isSidebarOpen = true" class="lg:hidden text-slate-500 mr-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 leading-tight">Pusat Karantina Medis & Rawat Ternak</h2>
                        <p class="text-xs text-slate-400">Isolasi darurat ternak sakit, pelacakan hari rawat, dan pelepasan verifikasi pakar</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 relative">
                    <div class="text-right hidden md:block">
                        <div class="text-sm font-bold text-slate-700">{{ user?.name || 'Petugas Lapangan' }}</div>
                        <div class="text-[11px] text-emerald-600 font-semibold uppercase tracking-wider">Anak Kandang</div>
                    </div>
                    <button @click="isDropdownOpen = !isDropdownOpen" 
                            @blur="setTimeout(() => isDropdownOpen = false, 250)" 
                            class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 font-bold uppercase transition hover:ring-2 hover:ring-emerald-300 border-2 border-white shadow-sm flex items-center justify-center cursor-pointer">
                        {{ user?.name?.charAt(0) || 'A' }}
                    </button>

                    <div v-show="isDropdownOpen" class="absolute right-0 top-12 w-64 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="text-xs font-bold text-slate-800">{{ user?.name }}</p>
                            <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ user?.email }}</p>
                            <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold bg-emerald-50 text-emerald-700 rounded-md">Operator Lapangan</span>
                        </div>
                        <div class="py-1">
                            <Link :href="route('profile.edit')" class="block px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Profil Akun</Link>
                            <Link :href="route('profile.edit')" class="block px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Sesuaikan Password</Link>
                        </div>
                        <div class="border-t border-slate-100 pt-1">
                            <Link :href="route('logout')" method="post" as="button" class="w-full text-left px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Logout Keluar</Link>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20 space-y-6">
                
                <!-- ROW 1: KARTU METRIK MEDIS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-rose-50/80 via-white to-white rounded-2xl p-5 border border-rose-200/60 shadow-sm">
                        <span class="text-xs font-bold text-rose-700 uppercase">Pasien Diisolasi</span>
                        <h3 class="text-2xl font-black text-rose-600 mt-1">{{ stats.total_pasien }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                        <p class="text-[11px] text-rose-500 font-medium mt-0.5">Terkunci di Karantina</p>
                    </div>

                    <div class="bg-gradient-to-br from-amber-50/80 via-white to-white rounded-2xl p-5 border border-amber-200/60 shadow-sm">
                        <span class="text-xs font-bold text-amber-700 uppercase">Kondisi Kritis (&lt; 50%)</span>
                        <h3 class="text-2xl font-black text-amber-700 mt-1">{{ stats.fase_kritis }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                        <p class="text-[11px] text-amber-600 font-medium mt-0.5">Butuh Injeksi & Pantauan Ketat</p>
                    </div>

                    <div class="bg-gradient-to-br from-sky-50/80 via-white to-white rounded-2xl p-5 border border-sky-200/60 shadow-sm">
                        <span class="text-xs font-bold text-sky-700 uppercase">Menuju Pulih (&ge; 50%)</span>
                        <h3 class="text-2xl font-black text-sky-700 mt-1">{{ stats.menuju_pulih }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                        <p class="text-[11px] text-sky-600 font-medium mt-0.5">Nafsu Makan Membaik</p>
                    </div>

                    <div class="bg-gradient-to-br from-indigo-50/80 via-white to-white rounded-2xl p-5 border border-indigo-200/60 shadow-sm">
                        <span class="text-xs font-bold text-indigo-700 uppercase">Kapasitas Kamar Isolasi</span>
                        <h3 class="text-2xl font-black text-indigo-700 mt-1">{{ stats.total_pasien }} / {{ stats.kapasitas_krt }} <span class="text-xs font-normal text-slate-400">Slot</span></h3>
                        <p class="text-[11px] text-indigo-600 font-medium mt-0.5">Kandang KRT-01</p>
                    </div>
                </div>

                <!-- ROW 2: BILAH CARI CEPAT UNTUK SAKIT MENDADAK -->
                <div class="bg-gradient-to-r from-rose-500 via-rose-600 to-red-600 rounded-2xl p-6 text-white shadow-lg shadow-rose-500/10">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <span class="inline-block bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider mb-2">
                                Tanggap Darurat Medis
                            </span>
                            <h3 class="text-2xl font-black tracking-tight">Kambing Sakit Mendadak di Lapangan?</h3>
                            <p class="text-xs text-rose-100 mt-1">
                                Ketik RFID kambing sehat untuk langsung mengisolasinya ke kandang KRT-01 dan menguncinya di rekam medis.
                            </p>
                        </div>

                        <div class="w-full md:w-80 relative">
                            <input v-model="queryCariKambing" type="text" placeholder="Ketik RFID / Ras kambing..." 
                                   class="w-full bg-white text-slate-800 placeholder-slate-400 px-4 py-2.5 rounded-xl text-xs font-semibold outline-none shadow-md focus:ring-2 focus:ring-white">
                            
                            <div v-if="kandidatSakit.length > 0" class="absolute left-0 right-0 top-12 bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden z-50 text-slate-700">
                                <div v-for="t in kandidatSakit" :key="t.id" 
                                     @click="bukaModalLapor(t)"
                                     class="p-3 hover:bg-rose-50 flex justify-between items-center cursor-pointer border-b border-slate-100 last:border-b-0 text-xs">
                                    <div>
                                        <div class="font-mono font-bold text-slate-800">{{ t.rfid_tag }}</div>
                                        <div class="text-[11px] text-slate-400">{{ t.ras }} • Kandang: {{ t.kandang }}</div>
                                    </div>
                                    <span class="bg-rose-100 text-rose-800 font-bold px-2.5 py-1 rounded-lg text-[10px]">
                                        + Laporkan Sakit
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROW 3: TABEL PASIEN KARANTINA TERKUNCI -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-slate-800 text-base">Daftar Pasien Karantina Medis Aktif</h4>
                                <span class="text-xs font-extrabold bg-rose-100 text-rose-800 px-2.5 py-0.5 rounded-full">
                                    {{ filteredPasien.length }} Pasien Terkunci
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">Ternak tidak akan keluar dari daftar ini sebelum diverifikasi sembuh 100% oleh pakar</p>
                        </div>

                        <div class="flex items-center gap-2.5 w-full sm:w-auto">
                            <select v-model="filterKondisi" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold outline-none focus:ring-2 focus:ring-emerald-200">
                                <option value="All">Semua Kondisi</option>
                                <option value="Kritis">Fase Kritis (&lt; 50%)</option>
                                <option value="Pemulihan">Menuju Pulih (&ge; 50%)</option>
                            </select>

                            <input v-model="searchQuery" type="text" placeholder="Cari RFID / Gejala..." 
                                   class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs outline-none focus:ring-2 focus:ring-emerald-200 w-full sm:w-56">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 text-xs uppercase bg-slate-50/70 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Tag RFID & Ras</th>
                                    <th class="px-6 py-4 font-semibold">Durasi Rawat</th>
                                    <th class="px-6 py-4 font-semibold">Gejala & Diagnosa</th>
                                    <th class="px-6 py-4 font-semibold">Tindakan Medis / Obat</th>
                                    <th class="px-6 py-4 font-semibold">Tingkat Kesembuhan</th>
                                    <th class="px-6 py-4 font-semibold text-center">Tindakan Petugas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="p in filteredPasien" :key="p.id" class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4">
                                        <div class="font-bold font-mono text-slate-800 text-sm">{{ p.rfid_tag }}</div>
                                        <div class="text-[11px] text-slate-400 font-medium">
                                            {{ p.ras }} • Bobot: {{ p.berat_kg }} Kg ({{ p.kandang }})
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="font-black font-mono text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-lg">
                                            Dirawat Hari ke-{{ p.hari_dirawat }}
                                        </span>
                                        <div class="text-[10px] text-slate-400 mt-1">Mulai: {{ p.tgl_mulai_sakit }}</div>
                                    </td>

                                    <td class="px-6 py-4 max-w-[200px] truncate" :title="p.gejala_sakit">
                                        <div class="font-bold text-slate-700">{{ p.gejala_sakit }}</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">Status: {{ p.keputusan_pakar }}</div>
                                    </td>

                                    <td class="px-6 py-4 max-w-[220px] truncate" :title="p.penanganan_medis">
                                        <div class="text-slate-700 font-medium">{{ p.penanganan_medis }}</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5 truncate">{{ p.catatan_pakar }}</div>
                                    </td>

                                    <td class="px-6 py-4 w-44">
                                        <div class="flex justify-between items-center text-xs font-bold mb-1">
                                            <span :class="p.persentase_sembuh < 50 ? 'text-rose-600' : 'text-emerald-600'">
                                                {{ p.persentase_sembuh }}% Sembuh
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-normal">
                                                {{ p.persentase_sembuh < 50 ? 'Kritis' : 'Membaik' }}
                                            </span>
                                        </div>
                                        <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                                            <div class="h-2 rounded-full transition-all duration-500"
                                                 :class="p.persentase_sembuh < 50 ? 'bg-rose-500' : 'bg-emerald-500'"
                                                 :style="{ width: `${p.persentase_sembuh}%` }"></div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="bukaModalProgres(p)" 
                                                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-xl font-bold transition border border-slate-200 shadow-xs">
                                                Update Progres
                                            </button>

                                            <button @click="bukaModalSembuh(p)" 
                                                    class="px-3 py-1.5 rounded-xl font-bold transition shadow-xs text-white"
                                                    :class="p.persentase_sembuh >= 80 ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-400 hover:bg-slate-500'">
                                                Verifikasi Sembuh
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="filteredPasien.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">
                                        Tidak ada pasien sakit dalam karantina. Semua ternak sehat prima!
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- MODAL 1: FORM LAPOR SAKIT -->
                <div v-if="isModalLaporOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-rose-50">
                            <div>
                                <h3 class="font-bold text-rose-900 text-base">Lapor Ternak Sakit Mendadak</h3>
                                <p class="text-xs text-rose-700">Kambing akan langsung dikunci ke isolasi KRT-01</p>
                            </div>
                            <button @click="isModalLaporOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitLapor" class="p-6 space-y-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="font-mono font-bold text-slate-800">{{ ternakTerpilihLapor?.rfid_tag }}</div>
                                <div class="text-slate-500">{{ ternakTerpilihLapor?.ras }} (Kandang asal: {{ ternakTerpilihLapor?.kandang }})</div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Gejala Fisik / Diagnosa Awal</label>
                                <input v-model="formLapor.gejala_sakit" type="text" required placeholder="Contoh: Mata berair, pincang, kembung"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-rose-200 outline-none">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Tindakan Medis / Obat yang Diberikan</label>
                                <textarea v-model="formLapor.penanganan_medis" rows="2" required placeholder="Contoh: Injeksi antibiotik 2ml, bersihkan luka..."
                                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-rose-200 outline-none"></textarea>
                            </div>

                            <div>
                                <div class="flex justify-between font-bold text-slate-600 uppercase mb-1">
                                    <span>Taksiran Kondisi Sembuh Awal</span>
                                    <span class="text-rose-600 font-mono">{{ formLapor.persentase_sembuh }}%</span>
                                </div>
                                <input type="range" min="10" max="70" step="5" v-model.number="formLapor.persentase_sembuh"
                                       class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-rose-600">
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <input id="pindah_krt_modal" type="checkbox" v-model="formLapor.pindah_karantina" class="rounded text-rose-600 focus:ring-rose-200">
                                <label for="pindah_krt_modal" class="font-bold text-slate-700">Pindahkan fisik kambing ke Kandang KRT-01</label>
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalLaporOpen = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl font-semibold">Batal</button>
                                <button type="submit" :disabled="formLapor.processing" class="px-5 py-2 text-white bg-rose-600 hover:bg-rose-700 font-bold rounded-xl transition shadow-sm">
                                    Simpan & Isolasi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODAL 2: UPDATE PROGRES MEDIS -->
                <div v-if="isModalProgresOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Update Catatan Perkembangan Medis</h3>
                                <p class="text-xs text-slate-400">Perbarui persentase pemulihan dan penambahan obat</p>
                            </div>
                            <button @click="isModalProgresOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitProgres" class="p-6 space-y-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="font-mono font-bold text-slate-800">{{ ternakTerpilihProgres?.rfid_tag }}</div>
                                <div class="text-slate-500">{{ ternakTerpilihProgres?.ras }} • Dirawat Hari ke-{{ ternakTerpilihProgres?.hari_dirawat }}</div>
                            </div>

                            <div>
                                <div class="flex justify-between font-bold text-slate-600 uppercase mb-1">
                                    <span>Tingkat Kesembuhan Terkini</span>
                                    <span class="text-emerald-700 font-mono font-bold text-sm">{{ formProgres.persentase_sembuh }}%</span>
                                </div>
                                <input type="range" min="10" max="95" step="5" v-model.number="formProgres.persentase_sembuh"
                                       class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-emerald-600">
                                <p class="text-[10px] text-slate-400 mt-1">*Maksimal 95%. Angka 100% hanya bisa disetujui lewat verifikasi pakar.</p>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Tindakan / Injeksi Tambahan Hari Ini</label>
                                <input v-model="formProgres.penanganan_tambahan" type="text" placeholder="Contoh: Pemberian vitamin B-kompleks booster"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Catatan Evaluasi Fisik</label>
                                <textarea v-model="formProgres.catatan_evaluasi" rows="2" placeholder="Catatan nafsu makan, keaktifan gerak..."
                                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none"></textarea>
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalProgresOpen = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl font-semibold">Batal</button>
                                <button type="submit" :disabled="formProgres.processing" class="px-5 py-2 text-white bg-slate-800 hover:bg-slate-900 font-bold rounded-xl transition shadow-sm">
                                    Simpan Progres
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODAL 3: VERIFIKASI SEHAT OLEH PAKAR -->
                <div v-if="isModalSembuhOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-emerald-50">
                            <div>
                                <h3 class="font-bold text-emerald-900 text-base">Verifikasi Sembuh 100% & Pelepasan</h3>
                                <p class="text-xs text-emerald-700">Kambing dinyatakan pulih dan dikeluarkan dari isolasi</p>
                            </div>
                            <button @click="isModalSembuhOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitSembuh" class="p-6 space-y-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="font-mono font-bold text-slate-800">{{ ternakTerpilihSembuh?.rfid_tag }}</div>
                                <div class="text-slate-500">{{ ternakTerpilihSembuh?.ras }} • Lama Rawat: {{ ternakTerpilihSembuh?.hari_dirawat }} Hari</div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Pilih Kandang Tujuan (Setelah Sembuh)</label>
                                <select v-model="formSembuh.kandang_tujuan" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                                    <option v-for="k in kandangs" :key="k.id" :value="k.kode_kandang">
                                        {{ k.kode_kandang }} - {{ k.nama_kandang }}
                                    </option>
                                    <option value="Blok A">Blok A (Penggemukan IoT)</option>
                                    <option value="Blok B">Blok B (Indukan Bunting)</option>
                                    <option value="Kandang A1">Kandang A1 (Kawin / Breeding)</option>
                                    <option value="Kandang B2">Kandang B2 (Penggemukan)</option>
                                    <option value="Kandang E1">Kandang E1 (Sapih)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Program Pemeliharaan Baru</label>
                                <select v-model="formSembuh.program_tujuan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                                    <option value="Produksi">Produksi</option>
                                    <option value="Pembibitan">Pembibitan</option>
                                    <option value="Penggemukan">Penggemukan</option>
                                    <option value="Siap Jual">Siap Jual</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Catatan Persetujuan Dokter / Pakar</label>
                                <textarea v-model="formSembuh.catatan_pelepasan" rows="2" placeholder="Catatan dokter hewan yang menyatakan sembuh total..."
                                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none"></textarea>
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalSembuhOpen = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl font-semibold">Batal</button>
                                <button type="submit" :disabled="formSembuh.processing" class="px-5 py-2 text-white bg-emerald-600 hover:bg-emerald-700 font-bold rounded-xl transition shadow-sm">
                                    Setujui 100% Sehat & Rilis
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>