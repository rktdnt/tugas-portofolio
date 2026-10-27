<?php
/**
 * Admin Dashboard — Portofolio Raya Mahardika Ibrahim
 * Akses khusus pemilik portofolio untuk mengelola pesan formulir kontak.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

// Inisialisasi status sesi admin
$is_authenticated = !empty($_SESSION['admin_authenticated']);
$feedback = null;
$login_error = null;

// 1. Tangani Permintaan Logout
if (isset($_GET['logout'])) {
    $_SESSION['admin_authenticated'] = false;
    session_destroy();
    header('Location: /admin');
    exit;
}

// 2. Tangani Login Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_login'])) {
    $input_password = (string)($_POST['password'] ?? '');

    if (!empty(ADMIN_PASSWORD) && hash_equals(ADMIN_PASSWORD, $input_password)) {
        $_SESSION['admin_authenticated'] = true;
        header('Location: /admin');
        exit;
    } else {
        $login_error = 'Password admin salah. Silakan periksa kembali.';
    }
}

// 3. Tangani Hapus Pesan (Hanya jika terautentikasi)
if ($is_authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete'])) {
    $delete_id = (int)($_POST['message_id'] ?? 0);
    $feedback = hapusPesanKontak($delete_id);
}

// 4. Ambil Data Pesan jika terautentikasi
$messages = [];
$stats = [
    'total' => 0,
    'proyek' => 0,
    'karir' => 0,
    'diskusi' => 0,
];

if ($is_authenticated) {
    $fetch_result = ambilSemuaPesanKontak();
    if ($fetch_result['success']) {
        $messages = $fetch_result['data'];
        $stats['total'] = count($messages);
        foreach ($messages as $msg) {
            $cat = strtolower($msg['kategori'] ?? '');
            if (isset($stats[$cat])) {
                $stats[$cat]++;
            } else {
                $stats['diskusi']++;
            }
        }
    } else {
        if (!$feedback) {
            $feedback = ['success' => false, 'message' => $fetch_result['message']];
        }
    }
}

/**
 * Format timestamp ISO ke format waktu lokal Indonesia yang rapi.
 */
function formatWaktuIndo(?string $isoString): string {
    if (!$isoString) return '-';
    try {
        $dt = new DateTime($isoString);
        $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $dt->format('d M Y, H:i') . ' WIB';
    } catch (Exception $e) {
        return htmlspecialchars($isoString);
    }
}

/**
 * Pemetaan label kategori yang manusiawi.
 */
function formatKategori(string $cat): string {
    return match (strtolower($cat)) {
        'proyek' => 'Kolaborasi Proyek',
        'karir' => 'Tawaran Karir',
        'diskusi' => 'Diskusi Teknis',
        default => ucfirst($cat),
    };
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard &mdash; Raya Mahardika Ibrahim</title>
  <meta name="robots" content="noindex, nofollow" />

  <!-- Google & Apple Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />

  <!-- CSS Eksternal -->
  <link rel="stylesheet" href="/style.css" />

  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['-apple-system', 'BlinkMacSystemFont', '"SF Pro Display"', '"SF Pro Text"', 'Inter', 'sans-serif'],
            mono: ['"SF Mono"', '"JetBrains Mono"', 'monospace'],
          },
          colors: {
            palette: {
              light: '#fcfcfc',
              silver: '#ababab',
              charcoal: '#525252',
              dark: '#191919',
            },
            apple: {
              canvas: '#121212',
              glassBase: 'rgba(255, 255, 255, 0.045)',
              glassHover: 'rgba(255, 255, 255, 0.08)',
              glassElevated: 'rgba(255, 255, 255, 0.12)',
              dock: 'rgba(25, 25, 25, 0.78)',
              borderSubtle: 'rgba(255, 255, 255, 0.08)',
              borderRim: 'rgba(255, 255, 255, 0.16)',
              borderBright: 'rgba(255, 255, 255, 0.32)',
            }
          },
          boxShadow: {
            'glass-sm': '0 4px 16px rgba(0, 0, 0, 0.3), inset 0 1px 1px rgba(255, 255, 255, 0.18)',
            'glass-md': '0 16px 36px -8px rgba(0, 0, 0, 0.45), inset 0 1px 1px rgba(255, 255, 255, 0.22)',
            'glass-lg': '0 24px 48px -12px rgba(0, 0, 0, 0.6), inset 0 1px 2px rgba(255, 255, 255, 0.28)',
            'glass-hover': '0 28px 56px -12px rgba(0, 0, 0, 0.7), inset 0 1px 2px rgba(255, 255, 255, 0.4)',
            'palette-glow': '0 10px 25px -4px rgba(252, 252, 252, 0.35), inset 0 1px 1px rgba(255, 255, 255, 0.7)',
          }
        }
      }
    }
  </script>

  <style>
    /* Ambient Liquid Animations & Specular Rim Light */
    @keyframes float-orb-1 {
      0% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(50px, -35px) scale(1.08); }
      100% { transform: translate(0, 0) scale(1); }
    }
    @keyframes float-orb-2 {
      0% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(-45px, 40px) scale(0.94); }
      100% { transform: translate(0, 0) scale(1); }
    }
    @keyframes float-orb-3 {
      0% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(35px, 30px) scale(1.06); }
      100% { transform: translate(0, 0) scale(1); }
    }
    @keyframes float-orb-4 {
      0% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(-30px, -40px) scale(1.04); }
      100% { transform: translate(0, 0) scale(1); }
    }
    .animate-liquid-1 { animation: float-orb-1 22s ease-in-out infinite; }
    .animate-liquid-2 { animation: float-orb-2 26s ease-in-out infinite; }
    .animate-liquid-3 { animation: float-orb-3 24s ease-in-out infinite; }
    .animate-liquid-4 { animation: float-orb-4 30s ease-in-out infinite; }

    .apple-glass-rim {
      position: relative;
    }
    .apple-glass-rim::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent 0%, rgba(252, 252, 252, 0.35) 50%, transparent 100%);
      pointer-events: none;
      border-radius: inherit;
    }
  </style>
</head>
<body class="bg-apple-canvas text-palette-silver font-sans antialiased min-h-screen relative selection:bg-white/20 selection:text-white">

  <!-- ======================================================================
       AMBIENT LIQUID BACKGROUND LAYER (Apple Glass Refraction Canvas)
       ====================================================================== -->
  <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden bg-[#121212]" aria-hidden="true">
    <div class="liquid-orb orb-1 animate-liquid-1 absolute -top-[10%] left-[10%] w-[540px] h-[540px] rounded-full bg-[radial-gradient(circle,rgba(252,252,252,0.12)_0%,rgba(252,252,252,0.02)_70%,transparent_100%)] blur-[95px] mix-blend-screen will-change-transform"></div>
    <div class="liquid-orb orb-2 animate-liquid-2 absolute top-[24%] right-[4%] w-[500px] h-[500px] rounded-full bg-[radial-gradient(circle,rgba(171,171,171,0.10)_0%,rgba(171,171,171,0.02)_70%,transparent_100%)] blur-[95px] mix-blend-screen will-change-transform"></div>
    <div class="liquid-orb orb-3 animate-liquid-3 absolute top-[58%] right-[18%] w-[460px] h-[460px] rounded-full bg-[radial-gradient(circle,rgba(82,82,82,0.16)_0%,rgba(82,82,82,0.02)_70%,transparent_100%)] blur-[95px] mix-blend-screen will-change-transform"></div>
    <div class="liquid-orb orb-4 animate-liquid-4 absolute bottom-[6%] left-[6%] w-[560px] h-[560px] rounded-full bg-[radial-gradient(circle,rgba(220,220,220,0.09)_0%,rgba(220,220,220,0.01)_70%,transparent_100%)] blur-[95px] mix-blend-screen will-change-transform"></div>
  </div>

  <?php if (!$is_authenticated): ?>
  <!-- ======================================================================
       TAMPILAN 1: FORM LOGIN ADMIN (Apple Liquid Glass Login Screen)
       ====================================================================== -->
  <div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md p-8 sm:p-10 bg-apple-glassBase backdrop-blur-2xl border border-apple-borderRim rounded-3xl shadow-glass-lg apple-glass-rim space-y-8">
      
      <!-- Brand & Icon -->
      <div class="text-center space-y-3">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/[0.08] border border-apple-borderRim text-palette-light shadow-glass-sm">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-palette-light">Portal Admin</h1>
        <p class="text-xs sm:text-sm text-palette-silver">
          Masukkan kata sandi untuk mengakses dasbor pesan formulir.
        </p>
      </div>

      <!-- Pesan Kesalahan Login -->
      <?php if ($login_error): ?>
        <div class="p-4 rounded-xl text-xs sm:text-sm bg-red-500/10 border border-red-500/30 text-red-200 flex items-center gap-2.5">
          <svg class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span><?= htmlspecialchars($login_error) ?></span>
        </div>
      <?php endif; ?>

      <!-- Form Password -->
      <form action="/admin" method="post" class="space-y-5">
        <input type="hidden" name="action_login" value="1" />

        <div class="space-y-2">
          <label for="password" class="block text-xs font-mono font-medium text-palette-silver">
            KATA SANDI ADMINISTRATOR
          </label>
          <input 
            type="password" 
            id="password" 
            name="password" 
            required 
            autofocus
            placeholder="••••••••••••"
            class="w-full px-4 py-3 bg-white/[0.04] backdrop-blur-md border border-apple-borderRim rounded-xl text-palette-light text-sm focus:outline-none focus:border-palette-light focus:ring-2 focus:ring-white/20 shadow-[inset_0_1px_2px_rgba(0,0,0,0.3)] transition-all placeholder:text-palette-charcoal"
          />
        </div>

        <button 
          type="submit" 
          class="w-full py-3.5 px-6 rounded-full font-bold text-sm text-palette-dark bg-palette-light border border-white/60 hover:bg-[#e5e5e5] transition-all flex items-center justify-center gap-2 shadow-palette-glow transform hover:-translate-y-0.5"
        >
          <span>Buka Dashboard</span>
          <span class="font-bold">&rarr;</span>
        </button>
      </form>

      <!-- Navigasi Balik -->
      <div class="text-center pt-2">
        <a href="/" class="text-xs text-palette-charcoal hover:text-palette-light transition-colors">
          &larr; Kembali ke Halaman Portofolio
        </a>
      </div>

    </div>
  </div>

  <?php else: ?>
  <!-- ======================================================================
       TAMPILAN 2: DASBOR ADMIN UTAMA (Authenticated Dashboard)
       ====================================================================== -->
  
  <!-- Header Floating Glass VisionOS -->
  <header class="sticky top-4 z-50 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="backdrop-blur-2xl bg-apple-dock/85 border border-apple-borderRim rounded-full px-4 sm:px-6 py-3 shadow-glass-md flex items-center justify-between transition-all">
      <div class="flex items-center gap-3">
        <a href="/admin" class="flex items-center gap-2">
          <span class="font-bold text-sm sm:text-base tracking-tight text-palette-light">
            Raya Mahardika
          </span>
          <span class="text-xs text-palette-charcoal font-mono hidden sm:inline">/ dasbor admin</span>
        </a>
        <span class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-mono bg-white/[0.06] text-palette-silver border border-white/10">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
          Supabase Connected
        </span>
      </div>

      <div class="flex items-center gap-2 sm:gap-3">
        <a 
          href="/" 
          target="_blank" 
          class="px-3 sm:px-4 py-1.5 rounded-full text-xs font-medium text-palette-silver hover:text-palette-light bg-white/[0.05] border border-white/10 hover:bg-white/[0.1] transition-all flex items-center gap-1.5"
        >
          <span>Portofolio</span>
          <span class="text-[10px]">&nearr;</span>
        </a>
        <a 
          href="/admin?logout=1" 
          class="px-3 sm:px-4 py-1.5 rounded-full text-xs font-semibold text-palette-light bg-red-500/20 border border-red-500/30 hover:bg-red-500/30 transition-all"
        >
          Keluar
        </a>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8">
    
    <!-- Judul & Keterangan Dasbor -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-palette-light tracking-tight">
          Pesan Formulir Kontak
        </h1>
        <p class="text-xs sm:text-sm text-palette-silver mt-1">
          Daftar seluruh pesan dari pengunjung website portofolio yang tersimpan di Supabase.
        </p>
      </div>
      <div>
        <a 
          href="/admin" 
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold text-palette-light bg-white/[0.07] border border-apple-borderRim hover:bg-white/[0.12] transition-all shadow-glass-sm"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Segarkan Data</span>
        </a>
      </div>
    </div>

    <!-- Alert Notifikasi Tindakan (Flash Feedback) -->
    <?php if ($feedback): ?>
      <div class="p-4 rounded-2xl text-xs sm:text-sm leading-relaxed <?= $feedback['success'] ? 'bg-white/[0.08] border border-white/25 text-palette-light' : 'bg-red-500/10 border border-red-500/30 text-red-200' ?>">
        <strong><?= $feedback['success'] ? '✓ Berhasil:' : '⚠ Perhatian:' ?></strong> <?= htmlspecialchars($feedback['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Kartu Statistik Singkat (Apple Glass Grid) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="p-5 bg-apple-glassBase backdrop-blur-2xl border border-apple-borderRim rounded-2xl shadow-glass-sm apple-glass-rim space-y-1">
        <span class="text-xs text-palette-silver font-mono">TOTAL PESAN</span>
        <div class="text-2xl sm:text-3xl font-extrabold text-palette-light"><?= $stats['total'] ?></div>
      </div>
      <div class="p-5 bg-apple-glassBase backdrop-blur-2xl border border-apple-borderRim rounded-2xl shadow-glass-sm apple-glass-rim space-y-1">
        <span class="text-xs text-palette-silver font-mono">KOLABORASI PROYEK</span>
        <div class="text-2xl sm:text-3xl font-extrabold text-palette-light"><?= $stats['proyek'] ?></div>
      </div>
      <div class="p-5 bg-apple-glassBase backdrop-blur-2xl border border-apple-borderRim rounded-2xl shadow-glass-sm apple-glass-rim space-y-1">
        <span class="text-xs text-palette-silver font-mono">TAWARAN KARIR</span>
        <div class="text-2xl sm:text-3xl font-extrabold text-palette-light"><?= $stats['karir'] ?></div>
      </div>
      <div class="p-5 bg-apple-glassBase backdrop-blur-2xl border border-apple-borderRim rounded-2xl shadow-glass-sm apple-glass-rim space-y-1">
        <span class="text-xs text-palette-silver font-mono">DISKUSI TEKNIS</span>
        <div class="text-2xl sm:text-3xl font-extrabold text-palette-light"><?= $stats['diskusi'] ?></div>
      </div>
    </div>

    <!-- Tabel Data Pesan Masuk (Apple Liquid Glass Table) -->
    <div class="bg-apple-glassBase backdrop-blur-2xl border border-apple-borderRim rounded-3xl shadow-glass-md apple-glass-rim overflow-hidden">
      <div class="p-5 sm:p-6 border-b border-white/[0.08] flex items-center justify-between">
        <h2 class="text-base sm:text-lg font-bold text-palette-light">
          Arsip Pesan Masuk
        </h2>
        <span class="text-xs font-mono text-palette-silver">
          <?= count($messages) ?> entri tercatat
        </span>
      </div>

      <?php if (empty($messages)): ?>
        <!-- Kondisi Kosong (Empty State) -->
        <div class="p-12 text-center space-y-3">
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/[0.05] border border-white/10 text-palette-charcoal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
          </div>
          <p class="text-sm font-medium text-palette-light">Belum ada pesan masuk</p>
          <p class="text-xs text-palette-silver max-w-sm mx-auto">
            Pesan yang dikirimkan melalui formulir kontak di website portofolio akan otomatis muncul pada tabel ini.
          </p>
        </div>
      <?php else: ?>
        <!-- Tabel Data Responsif -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs sm:text-sm min-w-[760px]">
            <thead class="bg-white/[0.04] border-b border-white/[0.08] text-palette-light font-mono text-xs">
              <tr>
                <th class="p-4 w-12 text-center">ID</th>
                <th class="p-4 w-44">Waktu</th>
                <th class="p-4 w-56">Pengirim</th>
                <th class="p-4 w-44">Kategori &amp; Layanan</th>
                <th class="p-4">Isi Pesan</th>
                <th class="p-4 w-24 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.06] text-palette-silver">
              <?php foreach ($messages as $item): ?>
                <?php 
                  $msg_id = (int)($item['id'] ?? 0);
                  $cat_raw = strtolower($item['kategori'] ?? 'diskusi');
                ?>
                <tr class="hover:bg-white/[0.03] transition-colors align-top">
                  <!-- ID -->
                  <td class="p-4 text-center font-mono text-palette-charcoal font-bold">
                    #<?= $msg_id ?>
                  </td>

                  <!-- Waktu -->
                  <td class="p-4 font-mono text-[11px] text-palette-silver">
                    <?= formatWaktuIndo($item['created_at'] ?? null) ?>
                  </td>

                  <!-- Pengirim -->
                  <td class="p-4 space-y-1">
                    <div class="font-bold text-palette-light text-sm">
                      <?= htmlspecialchars($item['nama'] ?? 'Tanpa Nama') ?>
                    </div>
                    <div>
                      <a href="mailto:<?= htmlspecialchars($item['email'] ?? '') ?>" class="text-palette-light hover:underline font-mono text-xs break-all">
                        <?= htmlspecialchars($item['email'] ?? '-') ?>
                      </a>
                    </div>
                    <?php if (!empty($item['telepon'])): ?>
                      <div class="text-[11px] font-mono text-palette-charcoal">
                        Tel: <a href="tel:<?= htmlspecialchars($item['telepon']) ?>" class="hover:text-palette-light transition-colors"><?= htmlspecialchars($item['telepon']) ?></a>
                      </div>
                    <?php endif; ?>
                  </td>

                  <!-- Kategori & Layanan -->
                  <td class="p-4 space-y-1.5">
                    <div>
                      <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-white/[0.08] text-palette-light border border-white/15">
                        <?= formatKategori($item['kategori'] ?? 'diskusi') ?>
                      </span>
                    </div>
                    <div class="text-[11px] font-mono text-palette-charcoal">
                      Layanan: <span class="text-palette-silver"><?= htmlspecialchars($item['layanan'] ?? '-') ?></span>
                    </div>
                  </td>

                  <!-- Isi Pesan -->
                  <td class="p-4">
                    <p class="text-xs sm:text-sm text-palette-light leading-relaxed whitespace-pre-wrap break-words">
                      <?= htmlspecialchars($item['pesan'] ?? '') ?>
                    </p>
                  </td>

                  <!-- Aksi Hapus -->
                  <td class="p-4 text-center">
                    <form action="/admin" method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesan #<?= $msg_id ?> dari <?= htmlspecialchars(addslashes($item['nama'] ?? 'pengirim ini')) ?>? Tindakan ini tidak dapat dibatalkan.');">
                      <input type="hidden" name="action_delete" value="1" />
                      <input type="hidden" name="message_id" value="<?= $msg_id ?>" />
                      <button 
                        type="submit" 
                        class="p-2 rounded-xl text-xs text-red-300 hover:text-white bg-red-500/10 hover:bg-red-500/30 border border-red-500/20 transition-all inline-flex items-center justify-center"
                        title="Hapus pesan ini"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div>

  </main>

  <footer class="border-t border-apple-borderSubtle mt-16 py-8 bg-apple-canvas/80 backdrop-blur-xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-palette-silver">
      <div>
        &copy; <?= date('Y') ?> <strong class="text-palette-light">Raya Mahardika Ibrahim</strong> &bull; Konsol Administrasi Portofolio
      </div>
      <div class="font-mono text-[11px] text-palette-charcoal">
        Backend: <span class="text-palette-light">Supabase Cloud</span> &bull; Status: <span class="text-emerald-400">Authenticated</span>
      </div>
    </div>
  </footer>

  <?php endif; ?>

</body>
</html>
