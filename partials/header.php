<?php $pageTitle = $pageTitle ?? 'TESDA Officials Directory'; $flash = take_flash(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Official directory of TESDA Central, Regional and Provincial Offices and TESDA Technology Institutions.">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= url('assets/favicon.svg') ?>">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{tesda:{blue:'#0038A8',navy:'#062B6F',red:'#CE1126',gold:'#F4C430',mist:'#F4F7FC'}}}}}</script>
  <link rel="stylesheet" href="<?= url('assets/app.css') ?>">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
  <header class="border-b border-blue-950/10 bg-white">
    <div class="h-1.5 bg-gradient-to-r from-tesda-blue via-tesda-blue to-tesda-red"></div>
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-5 px-4 py-4 sm:px-6 lg:px-8">
      <a href="<?= url() ?>" class="group flex min-w-0 items-center gap-3">
        <img src="<?= url('assets/img/tesda_logo.png') ?>" alt="TESDA logo" class="h-12 w-12 shrink-0 object-contain">
        <span class="min-w-0"><span class="block truncate text-lg font-extrabold tracking-tight text-tesda-navy">TESDA Officials Directory</span><span class="hidden text-sm text-slate-500 sm:block">Technical Education and Skills Development Authority</span></span>
      </a>
      <nav class="flex items-center gap-2 text-sm font-semibold" aria-label="Main navigation">
        <a href="<?= url() ?>" class="rounded-lg px-3 py-2 text-tesda-navy hover:bg-blue-50">Directory</a>
        <?php if (is_admin()): ?><a href="<?= url('/admin/index.php') ?>" class="rounded-lg bg-tesda-blue px-3 py-2 text-white hover:bg-tesda-navy">Manage</a><?php else: ?><a href="<?= url('/admin/login.php') ?>" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Admin</a><?php endif; ?>
      </nav>
    </div>
  </header>
  <?php if ($flash): ?><div class="mx-auto mt-5 max-w-7xl px-4 sm:px-6 lg:px-8"><div class="rounded-xl border px-4 py-3 text-sm <?= $flash['type']==='success'?'border-emerald-200 bg-emerald-50 text-emerald-800':'border-red-200 bg-red-50 text-red-800' ?>"><?= e($flash['message']) ?></div></div><?php endif; ?>
  <main>
