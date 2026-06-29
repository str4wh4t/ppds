<script setup>
import LegalLayout from '@/Layouts/LegalLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    appName: {
        type: String,
        required: true,
    },
    lastUpdated: {
        type: String,
        required: true,
    },
    contactEmail: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <LegalLayout>
        <Head :title="`Kebijakan Privasi — ${appName}`" />

        <article class="max-w-none text-gray-800 text-sm leading-relaxed sm:text-base">
            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl mb-2">Kebijakan Privasi</h1>
            <p class="text-sm text-gray-500 mb-6">
                {{ appName }} · Terakhir diperbarui: {{ lastUpdated }}
            </p>

            <p class="mb-4">
                Kebijakan Privasi ini menjelaskan bagaimana <strong>{{ appName }}</strong> (selanjutnya disebut
                &ldquo;Aplikasi&rdquo;) mengumpulkan, menggunakan, menyimpan, dan melindungi data pribadi Anda.
                Aplikasi ini digunakan untuk keperluan administrasi akademik dan pencatatan logbook Program Pendidikan
                Dokter Spesialis (PPDS) di lingkungan Fakultas Kedokteran Universitas Diponegoro.
            </p>
            <p class="mb-6">
                Dengan menggunakan Aplikasi (melalui situs web atau aplikasi mobile yang terhubung ke layanan ini),
                Anda menyetujui praktik yang dijelaskan dalam kebijakan ini.
            </p>

            <h2 class="mt-8 mb-3 text-lg font-semibold text-gray-900">1. Data yang Kami Kumpulkan</h2>
            <p class="mb-3">Kami dapat mengumpulkan kategori data berikut:</p>

            <h3 class="mt-4 mb-2 font-semibold text-gray-900">1.1 Data akun dan identitas</h3>
            <ul class="mb-4 list-disc space-y-1 pl-5">
                <li>Nama lengkap, username, alamat email, nomor identitas (misalnya NIM/NIK akademik), semester</li>
                <li>Peran pengguna dalam sistem (misalnya mahasiswa, dosen, kaprodi, admin)</li>
                <li>Unit/prodi terkait, dosen pembimbing, dan relasi akademik lain yang dikonfigurasi oleh institusi</li>
                <li>Kata sandi (disimpan dalam bentuk terenkripsi; kami tidak menyimpan kata sandi dalam bentuk teks biasa)</li>
            </ul>

            <h3>1.2 Data logbook dan aktivitas</h3>
            <ul>
                <li>Nama aktivitas, jenis aktivitas, deskripsi, tanggal dan waktu mulai/selesai</li>
                <li>Data stase, lokasi kegiatan, dan dosen terkait</li>
                <li>Rekapitulasi beban kerja mingguan (workload)</li>
                <li>Status persetujuan aktivitas (misalnya aktivitas yang melebihi batas jam kerja)</li>
            </ul>

            <h3>1.3 Data lokasi dan media (fitur check-in / check-out)</h3>
            <p>
                Jika Anda menggunakan fitur check-in atau check-out melalui aplikasi mobile, kami dapat mengumpulkan:
            </p>
            <ul>
                <li>Koordinat geografis (latitude dan longitude) saat check-in dan check-out</li>
                <li>Foto bukti check-in dan check-out yang Anda unggah</li>
            </ul>
            <p>
                Pengumpulan data ini dimaksudkan untuk memverifikasi kehadiran dan kegiatan logbook sesuai ketentuan
                program studi.
            </p>

            <h3>1.4 Data perangkat dan teknis</h3>
            <ul>
                <li>Alamat IP, user agent browser/aplikasi, dan preferensi bahasa (jika tersedia saat akses API)</li>
                <li>Token autentikasi API (Laravel Sanctum) untuk menjaga sesi login yang aman</li>
                <li>Cookie dan data sesi untuk pengguna yang mengakses melalui peramban web</li>
            </ul>

            <h3>1.5 Dokumen dan unggahan lain</h3>
            <ul>
                <li>Berkas portofolio, konsultasi, presentasi (speak), jadwal, dan dokumen panduan yang diunggah sesuai fitur yang tersedia</li>
                <li>Metadata berkas (misalnya ukuran file) yang diperlukan untuk pengelolaan dokumen</li>
            </ul>

            <h2>2. Cara Kami Menggunakan Data</h2>
            <p>Data yang dikumpulkan digunakan untuk:</p>
            <ul>
                <li>Autentikasi, otorisasi, dan pengelolaan akun pengguna</li>
                <li>Pencatatan, pemantauan, dan pelaporan aktivitas logbook PPDS</li>
                <li>Verifikasi kegiatan melalui check-in/check-out (lokasi dan foto, jika diaktifkan)</li>
                <li>Komunikasi akademik terkait konsultasi, persetujuan aktivitas, dan notifikasi internal</li>
                <li>Pemeliharaan keamanan sistem, pencegahan penyalahgunaan, dan peningkatan layanan</li>
                <li>Pemenuhan kewajiban administratif dan akademik institusi</li>
            </ul>

            <h2>3. Dasar Pemrosesan</h2>
            <p>
                Pemrosesan data dilakukan untuk kepentingan pelaksanaan pendidikan, administrasi akademik, dan
                pemenuhan kewajiban hukum institusi, serta berdasarkan persetujuan Anda sejauh diperlukan
                (misalnya saat mengaktifkan izin lokasi atau kamera di perangkat).
            </p>

            <h2>4. Berbagi Data dengan Pihak Ketiga</h2>
            <p>
                Kami tidak menjual data pribadi Anda. Data dapat diakses oleh pihak berikut sejauh diperlukan
                untuk operasional akademik:
            </p>
            <ul>
                <li>Administrator, kaprodi, dosen pembimbing, dan staf yang berwenang di lingkungan program studi</li>
                <li>Penyedia infrastruktur hosting/server yang mengelola penyimpanan data atas nama institusi</li>
            </ul>
            <p>
                Data dapat diungkapkan jika diwajibkan oleh peraturan perundang-undangan atau perintah yang sah
                dari otoritas berwenang.
            </p>

            <h2>5. Penyimpanan dan Keamanan</h2>
            <ul>
                <li>Data disimpan di server yang dikelola untuk keperluan operasional Aplikasi</li>
                <li>Kami menerapkan langkah-langkah keamanan yang wajar (misalnya enkripsi kata sandi, kontrol akses berbasis peran, autentikasi API)</li>
                <li>Foto dan dokumen disimpan pada penyimpanan aplikasi dengan pembatasan akses sesuai kebijakan institusi</li>
            </ul>
            <p>
                Meskipun kami berupaya melindungi data Anda, tidak ada metode transmisi atau penyimpanan elektronik
                yang sepenuhnya aman. Jika Anda mencurigai adanya pelanggaran keamanan, segera hubungi kami.
            </p>

            <h2>6. Retensi Data</h2>
            <p>
                Data disimpan selama diperlukan untuk tujuan akademik, administrasi program studi, dan pemenuhan
                kewajiban hukum. Penghapusan atau anonimisasi data dapat dilakukan sesuai kebijakan retensi
                institusi dan peraturan yang berlaku.
            </p>

            <h2>7. Hak Pengguna</h2>
            <p>Anda dapat memiliki hak untuk:</p>
            <ul>
                <li>Mengakses dan memperbarui data profil tertentu melalui fitur yang tersedia di Aplikasi</li>
                <li>Meminta koreksi data yang tidak akurat melalui administrator program studi</li>
                <li>Meminta penghapusan akun atau data tertentu, sejauh diizinkan oleh kebijakan institusi dan peraturan yang berlaku</li>
                <li>Menarik persetujuan untuk fitur opsional (misalnya lokasi/kamera) melalui pengaturan perangkat, dengan memahami bahwa beberapa fitur mungkin tidak dapat digunakan tanpa izin tersebut</li>
            </ul>

            <h2>8. Izin Perangkat (Aplikasi Mobile)</h2>
            <p>Aplikasi mobile dapat meminta izin berikut:</p>
            <ul>
                <li><strong>Kamera</strong> — untuk mengambil foto bukti check-in/check-out</li>
                <li><strong>Lokasi</strong> — untuk mencatat koordinat saat check-in/check-out</li>
                <li><strong>Jaringan internet</strong> — untuk sinkronisasi data dengan server</li>
            </ul>
            <p>
                Anda dapat mengelola izin ini melalui pengaturan sistem operasi perangkat Anda.
            </p>

            <h2>9. Anak-anak</h2>
            <p>
                Aplikasi ini ditujukan untuk pengguna dalam konteks pendidikan kedokteran spesialis dan tidak
                ditujukan untuk anak di bawah usia 13 tahun.
            </p>

            <h2>10. Perubahan Kebijakan</h2>
            <p>
                Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Perubahan material akan
                diinformasikan melalui Aplikasi atau saluran resmi institusi. Tanggal pembaruan terakhir
                tercantum di bagian atas halaman ini.
            </p>

            <h2>11. Hubungi Kami</h2>
            <p>
                Jika Anda memiliki pertanyaan tentang Kebijakan Privasi ini atau pengelolaan data pribadi Anda,
                silakan hubungi:
            </p>
            <p>
                <strong>Email:</strong>
                <a :href="`mailto:${contactEmail}`" class="text-indigo-600 hover:text-indigo-800">{{ contactEmail }}</a>
            </p>

            <div class="mt-10 border-t border-gray-200 pt-6">
                <Link href="/" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    ← Kembali ke halaman login
                </Link>
            </div>
        </article>
    </LegalLayout>
</template>

<style scoped>
article :deep(h2) {
    @apply mt-8 mb-3 text-lg font-semibold text-gray-900;
}

article :deep(h3) {
    @apply mt-4 mb-2 font-semibold text-gray-900;
}

article :deep(p) {
    @apply mb-4;
}

article :deep(ul) {
    @apply mb-4 list-disc space-y-1 pl-5;
}

article :deep(strong) {
    @apply font-semibold;
}
</style>
