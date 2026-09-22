<?php
// Shared by the add-office modal and the edit-office page.
$officeTypes = ['Central Office', 'Regional Office', 'Provincial Office', 'TESDA Technology Institution'];
?>
<input type="hidden" name="_token" value="<?= csrf_token() ?>">
<input type="hidden" name="id" value="<?= (int) $id ?>">
<div class="sm:col-span-2">
  <label class="label" for="office-name">Office name</label>
  <input id="office-name" class="field" name="name" required value="<?= e($office['name']) ?>">
</div>
<div>
  <label class="label" for="office-type">Office type</label>
  <select id="office-type" class="field" name="office_type">
    <?php foreach ($officeTypes as $officeType): ?>
      <option value="<?= e($officeType) ?>" <?= $office['office_type'] === $officeType ? 'selected' : '' ?>><?= e($officeType) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div>
  <label class="label" for="office-sort-order">Sort order</label>
  <input id="office-sort-order" class="field" name="sort_order" type="number" value="<?= (int) $office['sort_order'] ?>">
</div>
<div>
  <label class="label" for="office-region-code">Region code</label>
  <input id="office-region-code" class="field" name="region_code" value="<?= e($office['region_code']) ?>" placeholder="e.g. NCR">
</div>
<div>
  <label class="label" for="office-region-name">Region name</label>
  <input id="office-region-name" class="field" name="region_name" value="<?= e($office['region_name']) ?>" placeholder="e.g. National Capital Region">
</div>
<div class="sm:col-span-2">
  <label class="label" for="office-address">Address</label>
  <textarea id="office-address" class="field" name="address" rows="3"><?= e($office['address']) ?></textarea>
</div>
<?php foreach (['telephone' => 'Telephone', 'fax' => 'Fax', 'email' => 'Email', 'website' => 'Website'] as $key => $label): ?>
  <div>
    <label class="label" for="office-<?= $key ?>"><?= $label ?></label>
    <input id="office-<?= $key ?>" class="field" name="<?= $key ?>" <?= $key === 'email' ? 'type="email"' : '' ?> value="<?= e($office[$key]) ?>">
  </div>
<?php endforeach; ?>
<label class="sm:col-span-2 flex items-center gap-3 font-semibold text-slate-700">
  <input class="h-4 w-4 accent-blue-700" type="checkbox" name="is_active" <?= $office['is_active'] ? 'checked' : '' ?>>Visible in public directory
</label>
