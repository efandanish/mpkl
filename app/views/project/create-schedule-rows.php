<?php
$dates = isset($oldInput['schedule_date']) && is_array($oldInput['schedule_date']) ? $oldInput['schedule_date'] : [''];
if (!$dates || count($dates) > 1000) { $dates = ['']; }
$rowValue = function ($key, $index) use ($oldInput) {
    return isset($oldInput[$key]) && is_array($oldInput[$key]) && isset($oldInput[$key][$index]) && is_string($oldInput[$key][$index])
        ? htmlspecialchars($oldInput[$key][$index], ENT_QUOTES, 'UTF-8') : '';
};
foreach ($dates as $index => $unused): ?>
<div class="schedule-row border rounded bg-white p-3 mb-2">
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label">Tarikh Tuntutan</label>
            <input type="date" name="schedule_date[]" class="form-control" value="<?= $rowValue('schedule_date', $index) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Jumlah (RM)</label>
            <input type="number" name="schedule_amount[]" class="form-control" step="0.01" min="0" value="<?= $rowValue('schedule_amount', $index) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Jenis</label>
            <select name="schedule_type[]" class="form-select schedule-type">
                <?php foreach (['One-off', 'Ansuran', 'Pajakan/Sewa'] as $type): ?>
                <option value="<?= $type ?>" <?= $rowValue('schedule_type', $index) === $type ? 'selected' : '' ?>><?= $type ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
<?php endforeach; ?>
