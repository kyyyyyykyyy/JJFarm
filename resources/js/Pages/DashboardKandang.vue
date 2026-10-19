<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

// Menerima data awal dari controller web.php
const props = defineProps({
    initialTernaks: Array,
});

// Bikin state reactive biar data bisa berubah-ubah di layar
const ternaks = ref(props.initialTernaks);

// --- BAGIAN REAL-TIME (LARAVEL ECHO / REVERB) ---
onMounted(() => {
    // Mendengarkan channel WebSocket yang kita buat di file Event tadi
    window.Echo.channel('timbangan-channel')
        .listen('.TimbanganBaruMasuk', (e) => {
            console.log("Data masuk dari ESP32:", e);
            
            // Cek apakah ternak dengan RFID ini udah ada di tabel
            const index = ternaks.value.findIndex(t => t.rfid_tag === e.ternak.rfid_tag);
            
            if (index !== -1) {
                // Kalau udah ada, update datanya di baris tersebut
                ternaks.value[index] = e.ternak;
                // Opsional: Kasih efek highlight warna kuning sebentar biar kelihatan datanya berubah
                highlightRow(e.ternak.rfid_tag);
            } else {
                // Kalau ternak baru, tambahin ke baris paling atas tabel
                ternaks.value.unshift(e.ternak);
            }
        });
});

// Fungsi bantuan untuk efek highlight tabel saat ada update real-time
const highlightedRow = ref('');
const highlightRow = (rfid) => {
    highlightedRow.value = rfid;
    setTimeout(() => {
        highlightedRow.value = '';
    }, 2000); // Highlight hilang setelah 2 detik
};
</script>

<template>
    <Head title="Live Dashboard" />

    <div class="min-h-screen bg-gray-100 p-8">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Live Dashboard Timbangan</h1>
            <p class="text-gray-500 mb-8">Data tersinkronisasi otomatis dari perangkat ESP32</p>

            <!-- Tabel Data -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-800 text-white text-sm uppercase">
                            <th class="p-4">RFID Tag</th>
                            <th class="p-4">Profil</th>
                            <th class="p-4">Kandang</th>
                            <th class="p-4">Program</th>
                            <th class="p-4">Berat Terakhir</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Looping data ternak -->
                        <tr v-for="ternak in ternaks" :key="ternak.id" 
                            class="border-b transition duration-500"
                            :class="{'bg-yellow-100': highlightedRow === ternak.rfid_tag, 'hover:bg-gray-50': highlightedRow !== ternak.rfid_tag}">
                            
                            <td class="p-4 font-mono text-indigo-600 font-bold">{{ ternak.rfid_tag }}</td>
                            <td class="p-4">
                                <div class="font-semibold text-gray-800">{{ ternak.ras }} ({{ ternak.jenis_kelamin }})</div>
                                <div class="text-xs text-gray-500">Bpk: {{ ternak.bapak }} | Ibu: {{ ternak.mamak }}</div>
                            </td>
                            <td class="p-4">{{ ternak.kandang }}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                                    {{ ternak.program }}
                                </span>
                            </td>
                            <td class="p-4 text-lg font-bold text-orange-600">
                                {{ ternak.berat_terakhir }} Kg
                            </td>
                            <td class="p-4 text-center">
                                <!-- Indikator Sehat/Hamil dsb -->
                                <span v-if="ternak.status_kehamilan === 'Hamil'" class="px-2 py-1 bg-pink-100 text-pink-800 rounded text-xs font-semibold mr-1">
                                    Hamil
                                </span>
                            </td>
                            <td class="p-4">
                                <!-- Tombol edit (nanti kita fungsikan modal-nya) -->
                                <button class="text-indigo-500 hover:text-indigo-800 font-medium text-sm">
                                    Edit Profil
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Kalau data kosong -->
                        <tr v-if="ternaks.length === 0">
                            <td colspan="7" class="p-8 text-center text-gray-500">
                                Belum ada data ternak. Silakan timbang dari ESP32.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</template>