import { db } from './db.js';

/**
 * Sinkronisasi Offline presensi & realisasi KBM Reguler. Dipicu saat perangkat
 * kembali online: kirim seluruh draft (client_uuid masing-masing) ke endpoint
 * sync, hapus dari antrian lokal bila berhasil, tampilkan toast untuk konflik.
 * docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5.
 */
function token() {
    return document.querySelector('meta[name="sync-token"]')?.content ?? null;
}

async function kirimBatch(url, entries, table) {
    if (!entries.length || !token()) {
        return;
    }

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            Authorization: `Bearer ${token()}`,
        },
        body: JSON.stringify({ entries }),
    });

    if (!response.ok) {
        return;
    }

    const result = await response.json();

    for (const clientUuid of result.synced ?? []) {
        await table.delete(clientUuid);
    }

    for (const conflict of result.conflicts ?? []) {
        await table.delete(conflict.client_uuid);
        window.dispatchEvent(
            new CustomEvent('toast', { detail: { variant: 'info', message: conflict.message } })
        );
    }

    if ((result.synced ?? []).length) {
        window.dispatchEvent(
            new CustomEvent('toast', {
                detail: { variant: 'success', message: `${result.synced.length} entri berhasil disinkronkan.` },
            })
        );
    }
}

export async function syncNow() {
    if (!navigator.onLine) {
        return;
    }

    const draftPresensi = await db.draftPresensi.toArray();
    const draftRealisasi = await db.draftRealisasi.toArray();

    await kirimBatch('/api/v1/sync/presensi', draftPresensi, db.draftPresensi);
    await kirimBatch('/api/v1/sync/realisasi-kegiatan', draftRealisasi, db.draftRealisasi);
}

window.addEventListener('online', () => {
    syncNow();
});
