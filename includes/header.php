<?php
$page_title = $page_title ?? 'Ticketing';
$page_heading = $page_heading ?? $page_title;
$page_subtitle = $page_subtitle ?? '';
$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="style.css">

  <!-- Premium Font: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              yellow: '#f5e6a3', gold: '#eab308',
              dark: '#1c1c1c',
              gray: '#525252'
            }
          },
          fontFamily: {
            sans: ['Inter', 'system-ui', 'sans-serif'],
          }
        }
      }
    }
  </script>
</head>

<body
  class="min-h-screen bg-[#faf8f5] text-[#262626] font-sans antialiased flex flex-col selection:bg-brand-gold selection:text-brand-dark">
  <header class="flex flex-col md:flex-row bg-white border-b border-gray-200 shadow-sm relative z-50">
    <!-- Left brand column: Rich Gradient -->
    <div
      class="w-full md:w-72 bg-gradient-to-br from-yellow-400 to-amber-500 flex flex-col items-center justify-center py-6 px-6 text-center shrink-0 relative overflow-hidden group">
      <!-- Decorative background pattern (subtle circles) -->
      <div
        class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMCwwLDAsMC4wNSkiLz48L3N2Zz4=')] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
      </div>

      <a href="<?= $user ? 'dashboard.php' : 'index.php' ?>"
        class="relative z-10 no-underline flex flex-col items-center transform transition-transform duration-300 group-hover:scale-105">
        <span
          class="block text-brand-dark font-black text-3xl tracking-tighter uppercase leading-none drop-shadow-sm font-sans">Ticketing</span>
        <div class="flex items-center gap-2 w-full justify-center my-2 opacity-80">
          <span class="h-px w-8 bg-brand-dark"></span>
          <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-brand-dark">Help Desk</span>
          <span class="h-px w-8 bg-brand-dark"></span>
        </div>
      </a>
    </div>

    <!-- Right main header area -->
    <div class="flex-1 bg-white/95 backdrop-blur-sm flex flex-col justify-center px-6 py-4 md:px-8">
      <div class="flex flex-wrap items-center justify-between gap-4 w-full">
        <?php if ($user): ?>
          <?php
          $current_page = basename($_SERVER['PHP_SELF']);
          // Nav Link Styles: sleek, with icon support
          $nav_link_class = "group inline-flex items-center gap-2 px-3 py-2 text-sm font-bold uppercase tracking-wide transition-colors duration-200 hover:text-brand-gold relative";
          $active_class = "text-brand-gold";

          // Avatar Logic: Initials
          $username = $user['username'] ?? 'User';
          $initials = strtoupper(substr($username, 0, 2));
          // Random-ish vivid color for avatar bg based on name length
          $colors = ['bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-rose-500', 'bg-indigo-500'];
          $avatar_bg = $colors[strlen($username) % count($colors)];
          ?>

          <div class="flex items-center gap-6 lg:gap-8">
            <!-- Navigation Links with Icons -->
            <nav class="flex flex-wrap items-center gap-2" aria-label="Main navigation">
              <a href="dashboard.php"
                class="<?= $nav_link_class ?> <?= strpos($current_page, 'dashboard') !== false ? $active_class : 'text-gray-600' ?>">
                <div class="p-1.5 rounded-md bg-teal-50 group-hover:bg-teal-100 transition-colors">
                  <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                  </svg>
                </div>
                <span>Dashboard</span>
                <?php if (strpos($current_page, 'dashboard') !== false): ?>
                  <span class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-gold rounded-full"></span>
                <?php endif; ?>
              </a>
              <a href="tickets.php"
                class="<?= $nav_link_class ?> <?= strpos($current_page, 'tickets') !== false ? $active_class : 'text-gray-600' ?>">
                <div class="p-1.5 rounded-md bg-indigo-50 group-hover:bg-indigo-100 transition-colors">
                  <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                  </svg>
                </div>
                <span>Tickets</span>
                <?php if (strpos($current_page, 'tickets') !== false): ?>
                  <span class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-gold rounded-full"></span>
                <?php endif; ?>
              </a>
              <?php if ($user['role'] === 'admin' || $user['role'] === 'technician'): ?>
                <a href="kb.php"
                  class="<?= $nav_link_class ?> <?= strpos($current_page, 'kb') !== false ? $active_class : 'text-gray-600' ?>">
                  <div class="p-1.5 rounded-md bg-amber-50 group-hover:bg-amber-100 transition-colors">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                  </div>
                  <span>Knowledge Base</span>
                  <?php if (strpos($current_page, 'kb') !== false): ?>
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-gold rounded-full"></span>
                  <?php endif; ?>
                </a>

                <a href="reports.php"
                  class="<?= $nav_link_class ?> <?= strpos($current_page, 'reports') !== false ? $active_class : 'text-gray-600' ?>">
                  <div class="p-1.5 rounded-md bg-cyan-50 group-hover:bg-cyan-100 transition-colors">
                    <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                  </div>
                  <span>Reports</span>
                  <?php if (strpos($current_page, 'reports') !== false): ?>
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-gold rounded-full"></span>
                  <?php endif; ?>
                </a>
              <?php endif; ?>
              <?php if ($user['role'] === 'admin'): ?>
                <a href="users.php"
                  class="<?= $nav_link_class ?> <?= strpos($current_page, 'users') !== false || strpos($current_page, 'edit_user') !== false ? $active_class : 'text-gray-600' ?>">
                  <div class="p-1.5 rounded-md bg-rose-50 group-hover:bg-rose-100 transition-colors">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                  </div>
                  <span>Users</span>
                  <?php if (strpos($current_page, 'users') !== false || strpos($current_page, 'edit_user') !== false): ?>
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-gold rounded-full"></span>
                  <?php endif; ?>
                </a>
              <?php endif; ?>
            </nav>
          </div>

          <div class="flex items-center gap-6">
            <!-- Notification Bell -->
            <?php
            $unread_count = 0;
            if ($user) {
              if (function_exists('get_unread_count')) {
                $unread_count = get_unread_count($conn, $user['id']);
              }
            }
            ?>
            <a href="notifications.php" class="relative group p-2 rounded-full hover:bg-gray-100 transition-colors"
              aria-label="Notifications">
              <svg class="w-6 h-6 text-[#525252] group-hover:text-[#eab308] transition-colors" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <?php if ($unread_count > 0): ?>
                <span class="absolute top-1 right-1 flex h-4 w-4">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                  <span
                    class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[10px] font-bold text-white items-center justify-center">
                    <?= $unread_count > 9 ? '9+' : $unread_count ?>
                  </span>
                </span>
              <?php endif; ?>
            </a>
            <a href="create_ticket.php"
              class="group inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-gradient-to-r from-yellow-400 to-amber-500 text-brand-dark font-bold text-xs uppercase tracking-widest shadow-md hover:shadow-lg hover:from-yellow-300 hover:to-amber-400 transition-all transform hover:-translate-y-0.5 active:translate-y-0 border border-yellow-500/20">
              <svg class="w-4 h-4 text-brand-dark/80 group-hover:text-brand-dark transition-colors" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
              </svg>
              <span>New Ticket</span>
            </a>

            <!-- User Avatar & Sign Out Dropdown wrapper (simplified as a group for now) -->
            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
              <div class="flex flex-col items-end mr-1 hidden lg:block">
                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider leading-tight">Signed in
                  as</span>
                <span class="text-sm font-bold text-brand-dark leading-tight"><?= htmlspecialchars($username) ?></span>
              </div>

              <div class="relative group cursor-pointer">
                <!-- Avatar: Gradient Ring -->
                <div
                  class="w-10 h-10 rounded-full <?= $avatar_bg ?> flex items-center justify-center text-white font-bold text-sm shadow-sm ring-2 ring-offset-2 ring-gray-100 group-hover:ring-brand-gold/50 transition-all">
                  <?= $initials ?>
                </div>

                <!-- Simple Tooltip for logout -->
                <a href="logout.php"
                  class="absolute right-0 top-full mt-2 w-32 bg-white rounded-lg shadow-xl border border-gray-100 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all transform origin-top-right z-50">
                  <div class="px-4 py-2 border-b border-gray-100 mb-1">
                    <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($username) ?></p>
                  </div>
                  <div class="px-2">
                    <button
                      class="w-full text-left px-2 py-1.5 text-xs font-bold text-red-500 hover:bg-red-50 rounded uppercase tracking-wide flex items-center gap-2">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                      </svg>
                      Sign Out
                    </button>
                  </div>
                </a>
              </div>
            </div>
          </div>

        <?php else: ?>
          <!-- Not logged in -->
          <div class="flex-1"></div>
          <nav class="flex flex-wrap items-center gap-6 text-sm font-bold uppercase tracking-wider"
            aria-label="Sign in or register">
            <a href="index.php"
              class="px-2 py-1 text-gray-600 hover:text-brand-gold transition-colors flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400 group-hover:text-brand-gold" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Sign In
            </a>
            <a href="register.php"
              class="inline-flex items-center justify-center px-6 py-2.5 rounded-lg bg-brand-dark text-white font-semibold shadow-lg hover:shadow-xl hover:bg-black transition-all transform hover:-translate-y-0.5">
              <span>Register</span>
              <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </a>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Page title bar: light cream -->
  <div class="bg-[#f5e6a3]/30 border-b border-[#262626]/10 px-4 py-3">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-xl font-bold text-[#262626] uppercase tracking-tight"><?= htmlspecialchars($page_heading) ?></h1>
      <?php if ($page_subtitle !== ''): ?>
        <p class="text-[#262626] text-sm uppercase tracking-wide mt-1"><?= htmlspecialchars($page_subtitle) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <main class="max-w-7xl mx-auto px-4 py-8 flex-1 w-full">