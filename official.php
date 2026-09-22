<?php
require __DIR__ . '/bootstrap.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$official = null;
if ($id) {
    $stmt = db()->prepare('SELECT f.*, o.name office_name, o.office_type, o.region_name, o.address office_address, o.telephone office_telephone, o.email office_email, o.website office_website FROM officials f JOIN offices o ON o.id = f.office_id WHERE f.id = :id AND o.is_active = 1');
    $stmt->execute(['id' => $id]);
    $official = $stmt->fetch();
}

if (!$official) {
    http_response_code(404);
    $pageTitle = 'Official not found | TESDA Directory';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="mx-auto max-w-3xl px-4 py-20 text-center">
      <h1 class="text-3xl font-black text-tesda-navy">Official not found</h1>
      <p class="mt-3 text-slate-600">This profile is unavailable.</p>
      <a href="<?= url() ?>" class="mt-5 inline-block font-bold text-tesda-blue hover:underline">Return to directory</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = $official['name'] . ' | TESDA Officials Directory';
require __DIR__ . '/partials/header.php';
?>
<section class="bg-tesda-navy text-white">
  <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <a href="<?= url('office.php?id=' . (int)$official['office_id']) ?>" class="text-sm font-bold text-blue-200 hover:text-white">← Back to office</a>
    <div class="mt-7 flex flex-col gap-6 sm:flex-row sm:items-center">
      <?php if ($official['photo_path']): ?>
        <img src="<?= e(photo_url($official['photo_path'])) ?>" alt="Portrait of <?= e($official['name']) ?>" class="h-36 w-36 shrink-0 rounded-2xl bg-white object-cover shadow-lg">
      <?php else: ?>
        <div class="grid h-36 w-36 shrink-0 place-items-center rounded-2xl bg-white/15 text-6xl font-black text-white" aria-hidden="true"><?= e(mb_substr($official['name'], 0, 1)) ?></div>
      <?php endif; ?>
      <div>
        <p class="text-sm font-bold uppercase tracking-[.16em] text-blue-200">TESDA official</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl"><?= e($official['name']) ?></h1>
        <p class="mt-2 text-lg text-blue-100"><?= e($official['position']) ?></p>
      </div>
    </div>
  </div>
</section>

<section class="mx-auto grid max-w-5xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_340px] lg:px-8">
  <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
    <h2 class="text-xl font-extrabold text-tesda-navy">Office</h2>
    <a href="<?= url('office.php?id=' . (int)$official['office_id']) ?>" class="mt-4 inline-block text-lg font-bold text-tesda-blue hover:underline"><?= e($official['office_name']) ?></a>
    <p class="mt-2 text-sm text-slate-600"><?= e($official['office_type']) ?></p>
    <?php if ($official['region_name']): ?><p class="mt-1 text-sm text-slate-600"><?= e($official['region_name']) ?></p><?php endif; ?>
    <?php if ($official['office_address']): ?><p class="mt-5 leading-7 text-slate-700"><?= e($official['office_address']) ?></p><?php endif; ?>
  </div>
  <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-6">
    <h2 class="text-lg font-extrabold text-tesda-navy">Contact information</h2>
    <dl class="mt-5 space-y-5 text-sm">
      <?php if ($official['email']): ?><div><dt class="font-bold text-slate-500">Official email</dt><dd class="mt-1"><a href="mailto:<?= e($official['email']) ?>" class="break-all text-tesda-blue hover:underline"><?= e($official['email']) ?></a></dd></div><?php endif; ?>
      <?php if ($official['office_telephone']): ?><div><dt class="font-bold text-slate-500">Office telephone</dt><dd class="mt-1 text-slate-800"><?= e($official['office_telephone']) ?></dd></div><?php endif; ?>
      <?php if ($official['office_email']): ?><div><dt class="font-bold text-slate-500">Office email</dt><dd class="mt-1"><a href="mailto:<?= e($official['office_email']) ?>" class="break-all text-tesda-blue hover:underline"><?= e($official['office_email']) ?></a></dd></div><?php endif; ?>
      <?php if ($official['office_website']): ?><div><dt class="font-bold text-slate-500">Office website</dt><dd class="mt-1"><a href="<?= e($official['office_website']) ?>" target="_blank" rel="noopener" class="break-all text-tesda-blue hover:underline"><?= e($official['office_website']) ?></a></dd></div><?php endif; ?>
    </dl>
    <?php if (!$official['email'] && !$official['office_telephone'] && !$official['office_email'] && !$official['office_website']): ?><p class="mt-5 text-sm text-slate-500">No contact information is listed for this office.</p><?php endif; ?>
  </aside>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
