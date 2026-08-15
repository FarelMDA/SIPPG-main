import Dexie from 'dexie';

/**
 * Skema IndexedDB — dipakai untuk cache data yang dibutuhkan mengisi presensi &
 * realisasi KBM Reguler saat offline, dan antrian draft yang menunggu sync.
 * Sebelum konvergensi (docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5)
 * kalender dikunci `[kelasId+hariKe]` — kini kejadian KBM sudah berupa baris
 * Kegiatan sungguhan, dikunci `kegiatanId`.
 */
export const db = new Dexie('si-ppg-offline');

db.version(2).stores({
    generus: 'id, kelasId',
    kegiatanKbm: 'id, kelasId, tanggal',
    draftPresensi: 'clientUuid, kegiatanId, generusId',
    draftRealisasi: 'clientUuid, kegiatanId',
});

export async function simpanBootstrapKelas(kelasId, { generus, kegiatan_kbm }) {
    await db.generus.where('kelasId').equals(kelasId).delete();
    await db.generus.bulkPut(generus.map((g) => ({ ...g, kelasId })));

    await db.kegiatanKbm.where('kelasId').equals(kelasId).delete();
    await db.kegiatanKbm.bulkPut(
        kegiatan_kbm.map((k) => ({
            id: k.id,
            kelasId,
            tanggal: k.tanggal,
            materi: k.materi,
            realisasiStatus: k.realisasi_status,
            realisasiCatatan: k.realisasi_catatan,
        }))
    );
}

export async function antrikanPresensi(entry) {
    await db.draftPresensi.put(entry);
}

export async function antrikanRealisasi(entry) {
    await db.draftRealisasi.put(entry);
}
