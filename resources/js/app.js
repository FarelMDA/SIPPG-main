import './offline/sync.js';
import Chart from 'chart.js/auto';

// Dipakai slide Kehadiran di Laporan Bulanan (SRS-Fase-2 §3.5) — diekspos global
// karena chart diinstansiasi dari dalam x-data/x-init Alpine di Blade, bukan modul JS
// tersendiri. Chart.js sendiri tidak berinteraksi dengan Alpine, jadi aman digabung
// dengan larangan Alpine.start() ganda di bawah.
window.Chart = Chart;

// TIDAK mengimpor/menjalankan Alpine.js secara manual di sini — Livewire 3
// (@livewireScripts) sudah membundel & auto-start instance Alpine-nya
// sendiri. Memanggil Alpine.start() lagi di sini menghasilkan DUA instance
// Alpine berjalan bersamaan pada halaman yang sama ("Detected multiple
// instances of Alpine running"), yang bisa membuat wire:model tidak
// tersinkron dengan benar pada elemen yang juga dikendalikan directive Alpine
// (mis. x-show/x-data pada toggle show/hide password).

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js').catch((error) => {
            console.error('Gagal mendaftarkan service worker:', error);
        });
    });
}
