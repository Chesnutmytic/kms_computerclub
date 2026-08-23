-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 06 Agu 2026 pada 08.22
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `km_computerclub`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `alur_pembelajaran`
--

CREATE TABLE `alur_pembelajaran` (
  `id_alur` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_alur` varchar(255) NOT NULL,
  `tingkat_level` varchar(50) DEFAULT NULL,
  `status` enum('Draft','Published') DEFAULT 'Draft',
  `tgl_dibuat` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `alur_pembelajaran`
--

INSERT INTO `alur_pembelajaran` (`id_alur`, `id_user`, `nama_alur`, `tingkat_level`, `status`, `tgl_dibuat`) VALUES
(2, 4, 'draft', 'Pemula', 'Draft', '2026-07-16'),
(4, 4, 'Web Dev', 'Pemula', 'Published', '2026-07-18'),
(5, 4, 'Desain Grafis', 'Pemula', 'Published', '2026-07-21'),
(6, 5, 'Jaringan', 'Pemula', 'Published', '2026-07-21'),
(7, 5, 'Hardware', 'Pemula', 'Published', '2026-07-21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `arsip_materi`
--

CREATE TABLE `arsip_materi` (
  `id_arsip` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_approver` int(11) DEFAULT NULL,
  `judul_dokumen` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_media` varchar(255) DEFAULT NULL,
  `link_tautan` varchar(255) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Published','Rejected') DEFAULT 'Pending',
  `alasan_reject` text DEFAULT NULL,
  `tgl_unggah` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `arsip_materi`
--

INSERT INTO `arsip_materi` (`id_arsip`, `id_user`, `id_approver`, `judul_dokumen`, `deskripsi`, `kategori`, `file_path`, `file_media`, `link_tautan`, `tags`, `status`, `alasan_reject`, `tgl_unggah`) VALUES
(4, 5, 4, 'Html pdf', '<p>HTML (Hyper Text Markup Language) adalah sebuah bahasa markup yang digunakan untuk membuat sebuah halaman web dan menampilkan berbagai informasi di dalam sebuah browser Internet. Bermula dari sebuah bahasa yang sebelumnya banyak digunakan di dunia penerbitan dan percetakan yang disebut dengan SGML (Standard Generalized Markup Language), HTML adalah sebuah standar yang digunakan secara luas untuk menampilkan halaman web. HTML saat ini merupakan standar Internet yang didefinisikan dan dikendalikan penggunaannya oleh World Wide Web Consortium (W3C).</p>', 'Pemrograman', 'assets/uploads/materi_6a5f56c787b841.31009642.pptx', NULL, 'https://youtu.be/wriGST3vp5M?si=EieS3hyr86TwPCCf', '#html #web', 'Published', NULL, '2026-07-21'),
(5, 5, NULL, 'pending', '<p><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris sit amet semper enim. Mauris eu dictum velit. Nunc id convallis elit, vel molestie neque. Cras ac pretium ipsum. In aliquet lacus cursus, volutpat risus et, elementum erat. Praesent ac commodo magna, sed efficitur nibh. Pellentesque cursus lectus porttitor sollicitudin interdum. Donec non tellus eget diam pretium imperdiet nec id ante. Etiam efficitur erat scelerisque dapibus dictum. Aenean laoreet non lectus a sodales. Nunc sagittis diam quis enim mattis cursus. Maecenas et metus id est sollicitudin finibus ac ut arcu. Nunc tellus nunc, faucibus sed ex et, vulputate ullamcorper erat. Morbi a dui sed tortor accumsan varius varius non sem.</span></p>', 'Pemrograman', NULL, NULL, '', '#pending', 'Pending', NULL, '2026-07-21'),
(6, 5, 4, 'reject', '<p><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris sit amet semper enim. Mauris eu dictum velit. Nunc id convallis elit, vel molestie neque. Cras ac pretium ipsum. In aliquet lacus cursus, volutpat risus et, elementum erat. Praesent ac commodo magna, sed efficitur nibh. Pellentesque cursus lectus porttitor sollicitudin interdum. Donec non tellus eget diam pretium imperdiet nec id ante. Etiam efficitur erat scelerisque dapibus dictum. Aenean laoreet non lectus a sodales. Nunc sagittis diam quis enim mattis cursus. Maecenas et metus id est sollicitudin finibus ac ut arcu. Nunc tellus nunc, faucibus sed ex et, vulputate ullamcorper erat. Morbi a dui sed tortor accumsan varius varius non sem.</span></p><p class=\"ql-align-justify\"><span style=\"color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras fermentum ligula non metus bibendum, ullamcorper tempus enim luctus. In faucibus, nulla vitae faucibus vehicula, ligula est laoreet magna, ut accumsan risus elit sit amet ex. Nam pellentesque turpis lectus, eu ultrices turpis malesuada nec. Praesent non convallis eros, id interdum orci. Integer justo urna, scelerisque nec tempor luctus, luctus eu erat. Ut luctus quis arcu sed eleifend. Donec euismod ex vel feugiat porttitor. Nunc lacinia iaculis lacus id semper. Nullam semper mi vel sapien imperdiet consectetur. Aliquam lacinia consequat scelerisque. Proin rutrum dapibus sagittis. Phasellus id tempus velit. Nullam non mollis odio, sit amet maximus justo.</span></p><p><br></p>', 'Pemrograman', NULL, NULL, '', 'reject', 'Rejected', 'perbaiki deskripsi', '2026-07-21'),
(7, 5, 4, 'contoh', '<p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras fermentum ligula non metus bibendum, ullamcorper tempus enim luctus. In faucibus, nulla vitae faucibus vehicula, ligula est laoreet magna, ut accumsan risus elit sit amet ex. Nam pellentesque turpis lectus, eu ultrices turpis malesuada nec. Praesent non convallis eros, id interdum orci. Integer justo urna, scelerisque nec tempor luctus, luctus eu erat. Ut luctus quis arcu sed eleifend. Donec euismod ex vel feugiat porttitor. Nunc lacinia iaculis lacus id semper. Nullam semper mi vel sapien imperdiet consectetur. Aliquam lacinia consequat scelerisque. Proin rutrum dapibus sagittis. Phasellus id tempus velit. Nullam non mollis odio, sit amet maximus justo.</span></p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Suspendisse vel tincidunt eros. Etiam ut leo a odio malesuada tempus vel sed lorem. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Vivamus tincidunt semper nunc, a ornare quam luctus eu. Pellentesque semper sodales dolor, non posuere metus viverra in. Proin vel sem eget nisl vehicula cursus ut nec purus. Nam accumsan suscipit sodales.</span></p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Aenean vel vestibulum ex. Suspendisse potenti. Cras tempor elit ac velit ultricies facilisis. Duis rutrum aliquet est, a blandit leo venenatis at. Sed eget felis commodo, efficitur nisi sit amet, tincidunt enim. Vestibulum sit amet mattis augue. Nam id arcu nec urna finibus efficitur congue eu mi. Praesent risus ligula, scelerisque eu nisl at, dapibus malesuada diam. Curabitur commodo a leo et rhoncus. Phasellus vestibulum, tellus non feugiat accumsan, lectus felis tempus diam, maximus tempor urna sapien eu ex. Integer aliquet dui sit amet nibh malesuada ultricies id eu purus. Quisque pharetra euismod diam, vitae pharetra nulla pulvinar a. Nam sit amet pretium arcu. Fusce pulvinar fringilla erat. Duis vel augue pharetra erat vulputate tincidunt.</span></p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Praesent eu felis interdum, feugiat enim ac, porttitor libero. Etiam tristique eleifend justo et maximus. Proin bibendum sapien ac condimentum faucibus. Sed pretium felis sed sem hendrerit malesuada. Ut sollicitudin lorem velit, nec laoreet magna aliquet quis. Mauris mollis varius sem finibus luctus. Quisque vehicula est in metus feugiat, bibendum finibus augue feugiat. Etiam quis dignissim dui, id sollicitudin ante. Nam luctus ex non consequat eleifend. Suspendisse vehicula augue eu ex congue mattis. Sed sagittis, dui elementum aliquet dignissim, augue sapien elementum quam, ac condimentum lorem justo et velit.</span></p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Cras quis pharetra dolor, interdum laoreet justo. Aenean id leo sed mauris convallis dignissim. Aenean auctor diam at aliquam venenatis. Pellentesque pulvinar semper semper. Nulla sodales in odio eu aliquam. Nunc vel sodales augue, nec feugiat ante. Nam eu venenatis mi. Sed hendrerit massa nec arcu fermentum, in suscipit diam efficitur. Donec accumsan vehicula rutrum. Fusce a lectus eros. Vestibulum eget arcu arcu.</span></p><p><br></p><p>https://github.com/seaweedfs/seaweedfs</p>', 'Pemrograman', 'assets/uploads/materi_6a60e7085711b1.90954268.pdf', NULL, 'https://github.com/seaweedfs/seaweedfs', '#github', 'Published', NULL, '2026-07-22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `arsip_organisasi`
--

CREATE TABLE `arsip_organisasi` (
  `id_organisasi` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `judul_dokumen` varchar(255) NOT NULL,
  `kategori_organisasi` enum('Kelola Aset','Laporan Akhir (LPJ)','Galeri Kegiatan','SOP','Troubleshooting','Panduan Kaderisasi') NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_media` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Published','Rejected') DEFAULT 'Pending',
  `alasan_reject` text DEFAULT NULL,
  `id_approver` int(11) DEFAULT NULL,
  `tgl_unggah` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `arsip_organisasi`
--

INSERT INTO `arsip_organisasi` (`id_organisasi`, `id_user`, `judul_dokumen`, `kategori_organisasi`, `deskripsi`, `file_path`, `file_media`, `status`, `alasan_reject`, `id_approver`, `tgl_unggah`) VALUES
(1, 4, 'LPJ Tahun 2024-2025', 'Laporan Akhir (LPJ)', '<p>Laporan Pertanggung Jawaban Tahun 2024-2025</p>', 'assets/uploads/org_doc_6a60e499378987.51688989.pdf', 'assets/uploads/org_img_6a6100a17d2175.10435975.jpg', 'Published', NULL, 4, '2026-07-22 22:41:13'),
(2, 5, 'Contoh Pending', 'SOP', '<p>SOP</p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras fermentum ligula non metus bibendum, ullamcorper tempus enim luctus. In faucibus, nulla vitae faucibus vehicula, ligula est laoreet magna, ut accumsan risus elit sit amet ex. Nam pellentesque turpis lectus, eu ultrices turpis malesuada nec. Praesent non convallis eros, id interdum orci. Integer justo urna, scelerisque nec tempor luctus, luctus eu erat. Ut luctus quis arcu sed eleifend. Donec euismod ex vel feugiat porttitor. Nunc lacinia iaculis lacus id semper. Nullam semper mi vel sapien imperdiet consectetur. Aliquam lacinia consequat scelerisque. Proin rutrum dapibus sagittis. Phasellus id tempus velit. Nullam non mollis odio, sit amet maximus justo.</span></p>', NULL, NULL, 'Pending', NULL, NULL, '2026-07-22 22:42:31'),
(3, 5, 'Contoh Reject', 'Troubleshooting', '<p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras fermentum ligula non metus bibendum, ullamcorper tempus enim luctus. In faucibus, nulla vitae faucibus vehicula, ligula est laoreet magna, ut accumsan risus elit sit amet ex. Nam pellentesque turpis lectus, eu ultrices turpis malesuada nec. Praesent non convallis eros, id interdum orci. Integer justo urna, scelerisque nec tempor luctus, luctus eu erat. Ut luctus quis arcu sed eleifend. Donec euismod ex vel feugiat porttitor. Nunc lacinia iaculis lacus id semper. Nullam semper mi vel sapien imperdiet consectetur. Aliquam lacinia consequat scelerisque. Proin rutrum dapibus sagittis. Phasellus id tempus velit. Nullam non mollis odio, sit amet maximus justo.</span></p><p class=\"ql-align-justify\"><br></p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras fermentum ligula non metus bibendum, ullamcorper tempus enim luctus. In faucibus, nulla vitae faucibus vehicula, ligula est laoreet magna, ut accumsan risus elit sit amet ex. Nam pellentesque turpis lectus, eu ultrices turpis malesuada nec. Praesent non convallis eros, id interdum orci. Integer justo urna, scelerisque nec tempor luctus, luctus eu erat. Ut luctus quis arcu sed eleifend. Donec euismod ex vel feugiat porttitor. Nunc lacinia iaculis lacus id semper. Nullam semper mi vel sapien imperdiet consectetur. Aliquam lacinia consequat scelerisque. Proin rutrum dapibus sagittis. Phasellus id tempus velit. Nullam non mollis odio, sit amet maximus justo.</span></p><p class=\"ql-align-justify\"><br></p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras fermentum ligula non metus bibendum, ullamcorper tempus enim luctus. In faucibus, nulla vitae faucibus vehicula, ligula est laoreet magna, ut accumsan risus elit sit amet ex. Nam pellentesque turpis lectus, eu ultrices turpis malesuada nec. Praesent non convallis eros, id interdum orci. Integer justo urna, scelerisque nec tempor luctus, luctus eu erat. Ut luctus quis arcu sed eleifend. Donec euismod ex vel feugiat porttitor. Nunc lacinia iaculis lacus id semper. Nullam semper mi vel sapien imperdiet consectetur. Aliquam lacinia consequat scelerisque. Proin rutrum dapibus sagittis. Phasellus id tempus velit. Nullam non mollis odio, sit amet maximus justo.</span></p><p class=\"ql-align-justify\"><br></p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras fermentum ligula non metus bibendum, ullamcorper tempus enim luctus. In faucibus, nulla vitae faucibus vehicula, ligula est laoreet magna, ut accumsan risus elit sit amet ex. Nam pellentesque turpis lectus, eu ultrices turpis malesuada nec. Praesent non convallis eros, id interdum orci. Integer justo urna, scelerisque nec tempor luctus, luctus eu erat. Ut luctus quis arcu sed eleifend. Donec euismod ex vel feugiat porttitor. Nunc lacinia iaculis lacus id semper. Nullam semper mi vel sapien imperdiet consectetur. Aliquam lacinia consequat scelerisque. Proin rutrum dapibus sagittis. Phasellus id tempus velit. Nullam non mollis odio, sit amet maximus justo.</span></p><p class=\"ql-align-justify\"><br></p>', NULL, NULL, 'Rejected', 'kesalahan', 4, '2026-07-22 22:43:01');

-- --------------------------------------------------------

--
-- Struktur dari tabel `arsip_tag`
--

CREATE TABLE `arsip_tag` (
  `id_arsip` int(11) NOT NULL,
  `id_tag` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `catatan_pengalaman`
--

CREATE TABLE `catatan_pengalaman` (
  `id_catatan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_approver` int(11) DEFAULT NULL,
  `judul_kegiatan` varchar(255) NOT NULL,
  `jenis_kegiatan` enum('Lomba','Workshop','Pelatihan','Seminar') NOT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `pengalaman` text DEFAULT NULL,
  `kendala` text DEFAULT NULL,
  `solusi` text DEFAULT NULL,
  `gambar_dokumentasi` varchar(255) DEFAULT NULL,
  `link_tautan` varchar(255) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Published','Rejected') DEFAULT 'Pending',
  `alasan_reject` text DEFAULT NULL,
  `tgl_unggah` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `catatan_pengalaman`
--

INSERT INTO `catatan_pengalaman` (`id_catatan`, `id_user`, `id_approver`, `judul_kegiatan`, `jenis_kegiatan`, `kategori`, `pengalaman`, `kendala`, `solusi`, `gambar_dokumentasi`, `link_tautan`, `tags`, `status`, `alasan_reject`, `tgl_unggah`) VALUES
(7, 4, 4, 'contoh pengalaman', 'Pelatihan', 'Pemrograman', '<p><img src=\"../assets/uploads/editor/img_6a5f5f146c7a89.06439358.png\"></p><p><br></p><p><strong style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Lorem ipsum</strong><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\"> </span><em style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">dolor sit amet</em><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">, </span><u>consectetur </u><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">adipiscing elit. Mauris sit amet semper enim. Mauris eu dictum velit. Nunc id convallis elit, vel molestie neque. Cras ac pretium ipsum. In aliquet lacus cursus, volutpat risus et, elementum erat. Praesent ac commodo magna, sed efficitur nibh. Pellentesque cursus lectus porttitor sollicitudin interdum. </span>Donec non tellus eget diam pretium imperdiet nec id ante. Etiam efficitur erat scelerisque dapibus dictum<span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">. Aenean laoreet non lectus a sodales. Nunc sagittis diam quis enim mattis cursus. Maecenas et metus id est sollicitudin finibus ac ut arcu. Nunc tellus nunc, faucibus sed ex et, vulputate ullamcorper erat. Morbi a dui sed tortor accumsan varius varius non sem.</span></p>', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris sit amet semper enim. Mauris eu dictum velit. Nunc id convallis elit, vel molestie neque. Cras ac pretium ipsum. In aliquet lacus cursus, volutpat risus et, elementum erat. Praesent ac commodo magna, sed efficitur nibh. Pellentesque cursus lectus porttitor sollicitudin interdum. Donec non tellus eget diam pretium imperdiet nec id ante. Etiam efficitur erat scelerisque dapibus dictum. Aenean laoreet non lectus a sodales. Nunc sagittis diam quis enim mattis cursus. Maecenas et metus id est sollicitudin finibus ac ut arcu. Nunc tellus nunc, faucibus sed ex et, vulputate ullamcorper erat. Morbi a dui sed tortor accumsan varius varius non sem.', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris sit amet semper enim. Mauris eu dictum velit. Nunc id convallis elit, vel molestie neque. Cras ac pretium ipsum. In aliquet lacus cursus, volutpat risus et, elementum erat. Praesent ac commodo magna, sed efficitur nibh. Pellentesque cursus lectus porttitor sollicitudin interdum. Donec non tellus eget diam pretium imperdiet nec id ante. Etiam efficitur erat scelerisque dapibus dictum. Aenean laoreet non lectus a sodales. Nunc sagittis diam quis enim mattis cursus. Maecenas et metus id est sollicitudin finibus ac ut arcu. Nunc tellus nunc, faucibus sed ex et, vulputate ullamcorper erat. Morbi a dui sed tortor accumsan varius varius non sem.', 'assets/uploads/catatan/dok_6a5f5f9d29d9b6.90709506.png', NULL, '#pengalaman #selesai', 'Published', NULL, '2026-07-21'),
(8, 5, NULL, 'pending', 'Lomba', 'Pemrograman', '<p><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris sit amet semper enim. Mauris eu dictum velit. Nunc id convallis elit, vel molestie neque. Cras ac pretium ipsum. In aliquet lacus cursus, volutpat risus et, elementum erat. Praesent ac commodo magna, sed efficitur nibh. Pellentesque cursus lectus porttitor sollicitudin interdum. Donec non tellus eget diam pretium imperdiet nec id ante. Etiam efficitur erat scelerisque dapibus dictum. Aenean laoreet non lectus a sodales. Nunc sagittis diam quis enim mattis cursus. Maecenas et metus id est sollicitudin finibus ac ut arcu. Nunc tellus nunc, faucibus sed ex et, vulputate ullamcorper erat. Morbi a dui sed tortor accumsan varius varius non sem.</span></p>', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris sit amet semper enim. Mauris eu dictum velit. Nunc id convallis elit, vel molestie neque. Cras ac pretium ipsum. In aliquet lacus cursus, volutpat risus et, elementum erat. Praesent ac commodo magna, sed efficitur nibh. Pellentesque cursus lectus porttitor sollicitudin interdum. Donec non tellus eget diam pretium imperdiet nec id ante. Etiam efficitur erat scelerisque dapibus dictum. Aenean laoreet non lectus a sodales. Nunc sagittis diam quis enim mattis cursus. Maecenas et metus id est sollicitudin finibus ac ut arcu. Nunc tellus nunc, faucibus sed ex et, vulputate ullamcorper erat. Morbi a dui sed tortor accumsan varius varius non sem.', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris sit amet semper enim. Mauris eu dictum velit. Nunc id convallis elit, vel molestie neque. Cras ac pretium ipsum. In aliquet lacus cursus, volutpat risus et, elementum erat. Praesent ac commodo magna, sed efficitur nibh. Pellentesque cursus lectus porttitor sollicitudin interdum. Donec non tellus eget diam pretium imperdiet nec id ante. Etiam efficitur erat scelerisque dapibus dictum. Aenean laoreet non lectus a sodales. Nunc sagittis diam quis enim mattis cursus. Maecenas et metus id est sollicitudin finibus ac ut arcu. Nunc tellus nunc, faucibus sed ex et, vulputate ullamcorper erat. Morbi a dui sed tortor accumsan varius varius non sem.', NULL, NULL, '#pending', 'Pending', NULL, '2026-07-21'),
(11, 6, 5, 'Pengalaman cecep', 'Pelatihan', 'Hardware', '<p><span style=\"color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);\">Phasellus tincidunt feugiat sapien quis blandit. Ut gravida ipsum euismod, fermentum velit ultricies, elementum nunc. Ut laoreet, libero at pretium malesuada, ipsum odio consequat nunc, eu bibendum justo augue sit amet mi. Aenean nisi urna, dapibus non tristique eget, aliquam eget dolor. Proin felis magna, aliquam sed risus a, sagittis pulvinar turpis. Duis eget arcu magna. Maecenas pretium enim risus, quis faucibus erat elementum non. Fusce in turpis sapien. Cras semper dui nec pulvinar efficitur. Donec nec ligula mattis, ultrices ligula ac, porta leo. Nulla facilisi. Ut euismod, massa ut mattis congue, eros velit posuere tellus, ut dapibus massa nunc a est. Integer faucibus hendrerit est in tristique. Mauris eget lacus sodales, laoreet metus quis, auctor erat. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec volutpat commodo rutrum.</span></p>', 'Phasellus tincidunt feugiat sapien', 'Phasellus tincidunt feugiat sapien quis blandit. Ut gravida ipsum euismod, fermentum velit ultricies, elementum nunc. Ut laoreet, libero at pretium malesuada, ipsum odio consequat nunc, eu bibendum justo augue sit amet mi.', NULL, NULL, '#troubleshoot', 'Pending', NULL, '2026-08-04'),
(12, 6, NULL, 'cecep pending', 'Lomba', 'Pemrograman', '<p><span style=\"background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);\">Pellentesque eget sodales metus, a elementum felis. Aliquam aliquet aliquam lacus, in posuere elit rutrum sit amet. Nullam dictum dui justo, a eleifend ex finibus eget. Vestibulum turpis ante, porta eu elementum at, viverra ac dui. Pellentesque metus arcu, dignissim vitae est non, tristique gravida metus. Aenean quis neque in nunc rutrum imperdiet sit amet quis enim. Vivamus eget erat sed sem cursus feugiat ac in metus. Vestibulum sed diam non diam hendrerit egestas.</span></p>', 'Pellentesque eget sodales metus, a elementum felis. Aliquam aliquet aliquam lacus, in posuere elit rutrum sit amet. Nullam dictum dui justo, a eleifend ex finibus eget. Vestibulum turpis ante, porta eu elementum at, viverra ac dui. Pellentesque metus arcu, dignissim vitae est non, tristique gravida metus. Aenean quis neque in nunc rutrum imperdiet sit amet quis enim. Vivamus eget erat sed sem cursus feugiat ac in metus. Vestibulum sed diam non diam hendrerit egestas.', 'Pellentesque eget sodales metus, a elementum felis. Aliquam aliquet aliquam lacus, in posuere elit rutrum sit amet. Nullam dictum dui justo, a eleifend ex finibus eget. Vestibulum turpis ante, porta eu elementum at, viverra ac dui. Pellentesque metus arcu, dignissim vitae est non, tristique gravida metus. Aenean quis neque in nunc rutrum imperdiet sit amet quis enim. Vivamus eget erat sed sem cursus feugiat ac in metus. Vestibulum sed diam non diam hendrerit egestas.', NULL, NULL, '#pending', 'Pending', NULL, '2026-08-04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `catatan_pribadi`
--

CREATE TABLE `catatan_pribadi` (
  `id_notes` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_arsip` int(11) NOT NULL,
  `isi_notes` text DEFAULT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `catatan_pribadi`
--

INSERT INTO `catatan_pribadi` (`id_notes`, `id_user`, `id_arsip`, `isi_notes`, `tgl_update`) VALUES
(5, 5, 7, '', '2026-07-30 14:27:02'),
(8, 4, 7, 'awjdowajdi', '2026-07-30 15:46:27'),
(9, 6, 7, 'tes catatan cecep', '2026-08-03 18:48:18');

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_alur`
--

CREATE TABLE `detail_alur` (
  `id_detail` int(11) NOT NULL,
  `id_alur` int(11) NOT NULL,
  `id_arsip` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_alur`
--

INSERT INTO `detail_alur` (`id_detail`, `id_alur`, `id_arsip`) VALUES
(6, 4, 4),
(7, 4, 7);

-- --------------------------------------------------------

--
-- Struktur dari tabel `komentar_catatan`
--

CREATE TABLE `komentar_catatan` (
  `id_komentar` int(11) NOT NULL,
  `id_catatan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `tgl_komentar` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `komentar_catatan`
--

INSERT INTO `komentar_catatan` (`id_komentar`, `id_catatan`, `id_user`, `komentar`, `tgl_komentar`) VALUES
(2, 7, 4, 'mantap', '2026-07-22 23:45:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `like_catatan`
--

CREATE TABLE `like_catatan` (
  `id_like` int(11) NOT NULL,
  `id_catatan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tgl_like` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `like_catatan`
--

INSERT INTO `like_catatan` (`id_like`, `id_catatan`, `id_user`, `tgl_like`) VALUES
(2, 7, 4, '2026-07-22 23:45:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengguna`
--

CREATE TABLE `pengguna` (
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `kartu_pelajar` varchar(255) DEFAULT NULL,
  `status_akun` enum('Pending','Aktif','Ditolak') DEFAULT 'Pending',
  `alasan_masuk` text DEFAULT NULL,
  `role` enum('Super Admin','Admin','Anggota') NOT NULL DEFAULT 'Anggota'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengguna`
--

INSERT INTO `pengguna` (`id_user`, `nama_lengkap`, `kelas`, `username`, `password`, `kartu_pelajar`, `status_akun`, `alasan_masuk`, `role`) VALUES
(4, 'ketua', 'xii', 'ketua', '$2y$10$9ljBRBh6DhYBUebTDQA5VOBihl4d5VjdezbQ7Al8lap/uUYeIy5.G', NULL, 'Aktif', 'ketua', 'Super Admin'),
(5, 'pengurus', 'xi', 'pengurus', '$2y$10$O5OICJvooDgtyyL3zcwmDuGUJmjWOvp.LYDO/bcjqLAUB.JH2RBXe', NULL, 'Aktif', 'pengurus', 'Admin'),
(6, 'cecep', 'x', 'cecep', '$2y$10$ZxmCuW/dw5KZsgvQu7mt5O9y79WfyNx2yazaq1tOjfsaga9lCu23K', NULL, 'Aktif', 'suka komputer', 'Anggota'),
(8, 'dapit', 'x mipa 5', 'dapit', '$2y$10$.XkEMKeyKYsNm4XWcRrPy.UGcrTlfFvaYQwZunEf8ZLU1fNsIzP/u', 'assets/uploads/id_cards/kp_6a5f63136ff659.24905443.png', 'Ditolak', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris sit amet semper enim. Mauris eu dictum velit. Nunc id convallis elit, vel molestie neque. Cras ac pretium ipsum. In aliquet lacus cursus, volutpat risus et, elementum erat. Praesent ac commodo magna, sed efficitur nibh. Pellentesque cursus lectus porttitor sollicitudin interdum. Donec non tellus eget diam pretium imperdiet nec id ante. Etiam efficitur erat scelerisque dapibus dictum. Aenean laoreet non lectus a sodales. Nunc sagittis diam quis enim mattis cursus. Maecenas et metus id est sollicitudin finibus ac ut arcu. Nunc tellus nunc, faucibus sed ex et, vulputate ullamcorper erat. Morbi a dui sed tortor accumsan varius varius non sem.', 'Anggota'),
(9, 'idam', 'x mipa 6', 'idam', '$2y$10$pyyxhsd.xLNBn.z8PeBIo.0BMArmyfkHFFB2NAgs5TFx6Yr7mLtAW', 'assets/uploads/id_cards/kp_6a707c927e4ba4.48855988.jpg', 'Pending', '', 'Anggota');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id_pengumuman` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi_pengumuman` text NOT NULL,
  `tgl_dibuat` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengumuman`
--

INSERT INTO `pengumuman` (`id_pengumuman`, `id_user`, `judul`, `isi_pengumuman`, `tgl_dibuat`) VALUES
(1, 4, 'tes pengumuman', 'pengumuman', '2026-07-21 17:40:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `progress_belajar`
--

CREATE TABLE `progress_belajar` (
  `id_progress` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_arsip` int(11) NOT NULL,
  `tgl_selesai` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `progress_belajar`
--

INSERT INTO `progress_belajar` (`id_progress`, `id_user`, `id_arsip`, `tgl_selesai`) VALUES
(5, 4, 4, '2026-07-30 15:47:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tag`
--

CREATE TABLE `tag` (
  `id_tag` int(11) NOT NULL,
  `nama_tag` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `alur_pembelajaran`
--
ALTER TABLE `alur_pembelajaran`
  ADD PRIMARY KEY (`id_alur`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `arsip_materi`
--
ALTER TABLE `arsip_materi`
  ADD PRIMARY KEY (`id_arsip`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_approver` (`id_approver`);

--
-- Indeks untuk tabel `arsip_organisasi`
--
ALTER TABLE `arsip_organisasi`
  ADD PRIMARY KEY (`id_organisasi`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_approver` (`id_approver`);

--
-- Indeks untuk tabel `arsip_tag`
--
ALTER TABLE `arsip_tag`
  ADD PRIMARY KEY (`id_arsip`,`id_tag`),
  ADD KEY `id_tag` (`id_tag`);

--
-- Indeks untuk tabel `catatan_pengalaman`
--
ALTER TABLE `catatan_pengalaman`
  ADD PRIMARY KEY (`id_catatan`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_approver` (`id_approver`);

--
-- Indeks untuk tabel `catatan_pribadi`
--
ALTER TABLE `catatan_pribadi`
  ADD PRIMARY KEY (`id_notes`),
  ADD UNIQUE KEY `unique_user_arsip` (`id_user`,`id_arsip`),
  ADD KEY `id_arsip` (`id_arsip`);

--
-- Indeks untuk tabel `detail_alur`
--
ALTER TABLE `detail_alur`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_alur` (`id_alur`),
  ADD KEY `id_arsip` (`id_arsip`);

--
-- Indeks untuk tabel `komentar_catatan`
--
ALTER TABLE `komentar_catatan`
  ADD PRIMARY KEY (`id_komentar`),
  ADD KEY `id_catatan` (`id_catatan`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `like_catatan`
--
ALTER TABLE `like_catatan`
  ADD PRIMARY KEY (`id_like`),
  ADD UNIQUE KEY `unique_like` (`id_catatan`,`id_user`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id_pengumuman`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `progress_belajar`
--
ALTER TABLE `progress_belajar`
  ADD PRIMARY KEY (`id_progress`),
  ADD UNIQUE KEY `unique_progress` (`id_user`,`id_arsip`),
  ADD KEY `id_arsip` (`id_arsip`);

--
-- Indeks untuk tabel `tag`
--
ALTER TABLE `tag`
  ADD PRIMARY KEY (`id_tag`),
  ADD UNIQUE KEY `nama_tag` (`nama_tag`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `alur_pembelajaran`
--
ALTER TABLE `alur_pembelajaran`
  MODIFY `id_alur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `arsip_materi`
--
ALTER TABLE `arsip_materi`
  MODIFY `id_arsip` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `arsip_organisasi`
--
ALTER TABLE `arsip_organisasi`
  MODIFY `id_organisasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `catatan_pengalaman`
--
ALTER TABLE `catatan_pengalaman`
  MODIFY `id_catatan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `catatan_pribadi`
--
ALTER TABLE `catatan_pribadi`
  MODIFY `id_notes` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `detail_alur`
--
ALTER TABLE `detail_alur`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `komentar_catatan`
--
ALTER TABLE `komentar_catatan`
  MODIFY `id_komentar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `like_catatan`
--
ALTER TABLE `like_catatan`
  MODIFY `id_like` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id_pengumuman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `progress_belajar`
--
ALTER TABLE `progress_belajar`
  MODIFY `id_progress` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `tag`
--
ALTER TABLE `tag`
  MODIFY `id_tag` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `alur_pembelajaran`
--
ALTER TABLE `alur_pembelajaran`
  ADD CONSTRAINT `alur_pembelajaran_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `arsip_materi`
--
ALTER TABLE `arsip_materi`
  ADD CONSTRAINT `arsip_materi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `arsip_materi_ibfk_2` FOREIGN KEY (`id_approver`) REFERENCES `pengguna` (`id_user`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `arsip_organisasi`
--
ALTER TABLE `arsip_organisasi`
  ADD CONSTRAINT `arsip_organisasi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `arsip_organisasi_ibfk_2` FOREIGN KEY (`id_approver`) REFERENCES `pengguna` (`id_user`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `arsip_tag`
--
ALTER TABLE `arsip_tag`
  ADD CONSTRAINT `arsip_tag_ibfk_1` FOREIGN KEY (`id_arsip`) REFERENCES `arsip_materi` (`id_arsip`) ON DELETE CASCADE,
  ADD CONSTRAINT `arsip_tag_ibfk_2` FOREIGN KEY (`id_tag`) REFERENCES `tag` (`id_tag`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `catatan_pengalaman`
--
ALTER TABLE `catatan_pengalaman`
  ADD CONSTRAINT `catatan_pengalaman_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `catatan_pengalaman_ibfk_2` FOREIGN KEY (`id_approver`) REFERENCES `pengguna` (`id_user`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `catatan_pribadi`
--
ALTER TABLE `catatan_pribadi`
  ADD CONSTRAINT `catatan_pribadi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `catatan_pribadi_ibfk_2` FOREIGN KEY (`id_arsip`) REFERENCES `arsip_materi` (`id_arsip`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `detail_alur`
--
ALTER TABLE `detail_alur`
  ADD CONSTRAINT `detail_alur_ibfk_1` FOREIGN KEY (`id_alur`) REFERENCES `alur_pembelajaran` (`id_alur`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_alur_ibfk_2` FOREIGN KEY (`id_arsip`) REFERENCES `arsip_materi` (`id_arsip`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `komentar_catatan`
--
ALTER TABLE `komentar_catatan`
  ADD CONSTRAINT `komentar_catatan_ibfk_1` FOREIGN KEY (`id_catatan`) REFERENCES `catatan_pengalaman` (`id_catatan`) ON DELETE CASCADE,
  ADD CONSTRAINT `komentar_catatan_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `like_catatan`
--
ALTER TABLE `like_catatan`
  ADD CONSTRAINT `like_catatan_ibfk_1` FOREIGN KEY (`id_catatan`) REFERENCES `catatan_pengalaman` (`id_catatan`) ON DELETE CASCADE,
  ADD CONSTRAINT `like_catatan_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD CONSTRAINT `pengumuman_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `progress_belajar`
--
ALTER TABLE `progress_belajar`
  ADD CONSTRAINT `progress_belajar_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `progress_belajar_ibfk_2` FOREIGN KEY (`id_arsip`) REFERENCES `arsip_materi` (`id_arsip`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
