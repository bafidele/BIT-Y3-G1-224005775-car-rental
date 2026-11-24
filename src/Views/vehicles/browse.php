<h2 class="browse-page-title">Browse the fleet</h2>
<p class="browse-page-intro">Browse all vehicles and check their current availability. Customer accounts are free.</p>

<form class="vehicle-toolbar" id="vehicle-search-form" method="get" action="browse.php" data-api-url="api/vehicles.php" data-authenticated="<?= isAuthenticated() ? '1' : '0' ?>" data-customer="<?= isCustomerAccount() ? '1' : '0' ?>">
    <label>Search cars
        <input id="vehicle-search" type="search" name="q" maxlength="80" placeholder="Model, type, or plate" value="<?= e($query) ?>">
    </label>
    <label>Availability
        <select id="vehicle-status" name="status">
            <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All cars</option>
            <option value="available" <?= $statusFilter === 'available' ? 'selected' : '' ?>>Available</option>
            <option value="rented" <?= $statusFilter === 'rented' ? 'selected' : '' ?>>Rented</option>
            <option value="maintenance" <?= $statusFilter === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
        </select>
    </label>
    <button class="btn-blue" type="submit">Filter</button>
    <span class="vehicle-result-count" id="vehicle-result-count" aria-live="polite"><?= count($cars) ?> vehicle<?= count($cars) === 1 ? '' : 's' ?></span>
</form>

<p class="sr-only" id="vehicle-search-status" aria-live="polite"></p>
<div class="car-grid" id="vehicle-grid">
    <?php if (!$cars): ?>
        <p class="empty-state vehicle-empty">No cars match your search.</p>
    <?php else: ?>
        <?php foreach ($cars as $car): ?>
            <?php
            $photo = basename((string)($car['photo'] ?? ''));
            $photoAllowed = preg_match('/^[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$/i', $photo) === 1;
            $photoExists = $photoAllowed && is_file(dirname(__DIR__, 3) . '/uploads/' . $photo);
            $photoUrl = $photoExists ? 'uploads/' . rawurlencode($photo) : 'assets/car-placeholder.svg';
            ?>
            <article class="car-card">
                <img class="vehicle-photo" src="<?= e($photoUrl) ?>" alt="<?= e($car['model']) ?>" loading="lazy">
                <h3><?= e($car['model']) ?></h3>
                <p><strong>Type:</strong> <?= e($car['type']) ?></p>
                <p><strong>Plate:</strong> <?= e($car['plate_no']) ?></p>
                <p><strong>Price:</strong> $<?= number_format((float)$car['daily_rate'], 2) ?> / day</p>
                <?php if ((string)$car['status'] === 'available'): ?>
                    <span class="stock-availability">AVAILABLE NOW</span>
                    <?php if (isAuthenticated()): ?>
                        <?php $rentUrl = 'add_rental.php?vehicle_id=' . (int)$car['vehicle_id'] . (isCustomerAccount() ? '&show_terms=1' : ''); ?>
                        <a href="<?= e($rentUrl) ?>" class="stock-rent-button">Rent this car</a>
                    <?php else: ?>
                        <a href="register.php" class="btn-green browse-register-button">Create a free account to rent</a>
                    <?php endif; ?>
                <?php elseif ((string)$car['status'] === 'rented'): ?>
                    <button type="button" class="btn-red car-rented-button" disabled>This car has been rented</button>
                    <p class="car-return-date"><strong>Expected return:</strong>
                        <?php if (!empty($car['expected_return_date'])): ?>
                            <?= e(date('d M Y', strtotime((string)$car['expected_return_date']))) ?><?= !empty($car['expected_return_time']) ? ' at ' . e(substr((string)$car['expected_return_time'], 0, 5)) : '' ?>
                        <?php else: ?>Date not available<?php endif; ?>
                    </p>
                <?php else: ?>
                    <span class="badge maintenance">Under maintenance</span>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script src="assets/js/browse.js" defer></script>
