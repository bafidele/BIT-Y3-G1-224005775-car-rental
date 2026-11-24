<div class="page-heading">
    <div><p class="eyebrow">FLEET</p><h1>Vehicle management</h1></div>
    <p>Add, update, or remove fleet vehicles. Cars with rental history stay in the records.</p>
</div>

<?php if (is_array($notice)): ?><div class="<?= e($notice[0]) ?>" role="status"><?= e($notice[1]) ?></div><?php endif; ?>

<section class="customer-card">
    <h2>Add a vehicle</h2>
    <form method="post" action="vehicles.php" class="form-inline">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="add">
        <label>Registration plate<input type="text" name="plate_no" maxlength="20" required></label>
        <label>Model<input type="text" name="model" maxlength="50" required></label>
        <label>Type<input type="text" name="type" maxlength="50" required></label>
        <label>Daily rate ($)<input type="number" name="daily_rate" min="0.01" max="99999999.99" step="0.01" required></label>
        <button type="submit" class="btn-green">Add vehicle</button>
    </form>
</section>

<h2>Fleet vehicles</h2>
<?php if (!$vehicles): ?>
    <div class="empty-state">No vehicles are listed yet.</div>
<?php else: ?>
    <div class="table-scroll"><table>
        <thead><tr><th>Plate</th><th>Model</th><th>Type</th><th>Daily rate</th><th>Status</th><th>Manage</th></tr></thead>
        <tbody>
        <?php foreach ($vehicles as $vehicle): ?>
            <tr>
                <td><?= e($vehicle['plate_no']) ?></td>
                <td><?= e($vehicle['model']) ?></td>
                <td><?= e($vehicle['type']) ?></td>
                <td>$<?= number_format((float)$vehicle['daily_rate'], 2) ?></td>
                <td><span class="badge <?= e($vehicle['status']) ?>"><?= e(ucfirst((string)$vehicle['status'])) ?></span></td>
                <td><div class="receipt-actions">
                    <details class="vehicle-edit-details">
                        <summary class="button-ghost">Edit details</summary>
                        <form method="post" action="vehicles.php" class="vehicle-edit-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="vehicle_id" value="<?= (int)$vehicle['vehicle_id'] ?>">
                            <label>Plate<input type="text" name="plate_no" maxlength="20" value="<?= e($vehicle['plate_no']) ?>" required></label>
                            <label>Model<input type="text" name="model" maxlength="50" value="<?= e($vehicle['model']) ?>" required></label>
                            <label>Type<input type="text" name="type" maxlength="50" value="<?= e($vehicle['type']) ?>" required></label>
                            <label>Daily rate ($)<input type="number" name="daily_rate" min="0.01" max="99999999.99" step="0.01" value="<?= e($vehicle['daily_rate']) ?>" required></label>
                            <button type="submit" class="btn-blue">Save changes</button>
                        </form>
                    </details>
                    <?php if (in_array((string)$vehicle['status'], ['available', 'maintenance'], true)): ?>
                        <form method="post" action="vehicles.php">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="maintenance">
                            <input type="hidden" name="vehicle_id" value="<?= (int)$vehicle['vehicle_id'] ?>">
                            <input type="hidden" name="status" value="<?= $vehicle['status'] === 'maintenance' ? 'available' : 'maintenance' ?>">
                            <button type="submit" class="button-ghost"><?= $vehicle['status'] === 'maintenance' ? 'Make available' : 'Mark maintenance' ?></button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="vehicles.php" onsubmit="return confirm('Remove this vehicle? Vehicles with rental history cannot be deleted.');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="vehicle_id" value="<?= (int)$vehicle['vehicle_id'] ?>">
                        <button type="submit" class="btn-red">Delete</button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
