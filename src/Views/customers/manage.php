<div class="page-heading">
    <div><p class="eyebrow">ACCOUNTS &amp; RENTALS</p><h1>Customer management</h1></div>
    <p>Review payment confirmations, approve rentals, track completed rentals, and maintain customer records.</p>
</div>
<?php if (is_array($notice)): ?><div class="<?= e($notice[0]) ?>" role="status"><?= e($notice[1]) ?></div><?php endif; ?>

<?php if (!$customers): ?>
    <div class="empty-state">No customer accounts have been created yet.</div>
<?php endif; ?>

<?php foreach ($customers as $customer): ?>
    <?php
    $customerId = (int)$customer['customer_id'];
    $picture = (string)($customer['profile_picture'] ?? '');
    $validPicture = preg_match('#^profiles/[a-f0-9]{32}\.(jpg|png|webp)$#', $picture) === 1;
    $rentals = $rentalsByCustomer[$customerId] ?? [];
    ?>
    <section class="customer-card">
        <div class="customer-card-heading">
            <div class="customer-profile-heading">
                <?php if ($validPicture): ?>
                    <img class="profile-avatar profile-avatar-small" src="uploads/<?= e($picture) ?>" alt="">
                <?php else: ?>
                    <div class="profile-avatar profile-avatar-small profile-avatar-placeholder" aria-hidden="true"><?= e(strtoupper(substr((string)$customer['full_name'], 0, 1))) ?></div>
                <?php endif; ?>
                <div>
                    <h2><?= e($customer['full_name']) ?></h2>
                    <p><?= e($customer['email']) ?> · <?= e($customer['phone']) ?> · ID <?= e($customer['id_no']) ?></p>
                    <p><?= e($customer['district_city']) ?> · Account created <?= e(date('d M Y', strtotime((string)$customer['created_at']))) ?></p>
                </div>
            </div>
            <div class="customer-actions">
                <span class="badge verified">Free account</span>
                <details class="customer-edit-details">
                    <summary class="button-ghost">Edit customer</summary>
                    <form method="post" action="customers.php" class="customer-edit-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="update_customer">
                        <input type="hidden" name="customer_id" value="<?= $customerId ?>">
                        <label>Full name<input name="full_name" maxlength="100" value="<?= e($customer['full_name']) ?>" required></label>
                        <label>Email<input type="email" name="email" maxlength="100" value="<?= e($customer['email']) ?>" required></label>
                        <label>Phone<input name="phone" maxlength="20" value="<?= e($customer['phone']) ?>" required></label>
                        <label>District / city<input name="district_city" maxlength="50" value="<?= e($customer['district_city']) ?>" required></label>
                        <label>National ID<input name="id_no" maxlength="16" value="<?= e($customer['id_no']) ?>" required></label>
                        <button class="btn-blue" type="submit">Save customer</button>
                    </form>
                </details>
                <?php if ((int)$customer['total_rentals'] === 0): ?>
                    <form method="post" action="customers.php" onsubmit="return confirm('Delete this customer account?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="delete_customer">
                        <input type="hidden" name="customer_id" value="<?= $customerId ?>">
                        <button class="btn-red" type="submit">Delete account</button>
                    </form>
                <?php else: ?>
                    <span class="muted customer-record-note">Account retained with rental history</span>
                <?php endif; ?>
            </div>
        </div>

        <h3>Rental history <span class="subtle-count"><?= (int)$customer['total_rentals'] ?> rental<?= (int)$customer['total_rentals'] === 1 ? '' : 's' ?> · <?= (int)$customer['completed_rentals'] ?> completed</span></h3>
        <?php if ((int)$customer['completed_rentals'] >= 10): ?>
            <p class="reward-notice">This customer qualifies for 5% off every new rental.</p>
        <?php endif; ?>
        <?php if (!$rentals): ?>
            <p class="muted">This customer has not rented a car yet.</p>
        <?php else: ?>
            <div class="table-scroll"><table>
                <thead><tr><th>Receipt</th><th>Vehicle</th><th>Pick-up</th><th>Return</th><th>Completed at</th><th>Total fee</th><th>Status</th><th>Rental actions</th></tr></thead>
                <tbody>
                <?php foreach ($rentals as $rental): ?>
                    <?php $isComplete = in_array((string)$rental['status'], ['returned', 'completed'], true); ?>
                    <tr>
                        <td>R-<?= str_pad((string)$rental['rental_id'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td><?= e($rental['model']) ?> (<?= e($rental['plate_no']) ?>)</td>
                        <td><?= e(date('d M Y', strtotime((string)$rental['start_date']))) ?> <?= e(substr((string)$rental['start_time'], 0, 5)) ?></td>
                        <td><?= e(date('d M Y', strtotime((string)$rental['end_date']))) ?> <?= e(substr((string)$rental['return_time'], 0, 5)) ?></td>
                        <td><?= !empty($rental['completed_at']) ? e(date('d M Y, H:i', strtotime((string)$rental['completed_at']))) : '—' ?></td>
                        <td>$<?= number_format((float)$rental['total_cost'], 2) ?><?php if ((float)$rental['discount_amount'] > 0): ?><small class="discount-note">5% reward applied</small><?php endif; ?></td>
                        <td><?= $isComplete ? 'Completed' : match ((string)$rental['status']) {
                                'payment_pending' => 'Awaiting customer payment',
                                'payment_submitted' => 'Payment submitted · needs review',
                                'active' => 'Rental approved',
                                'cancelled' => 'Request cancelled',
                                default => e(ucfirst((string)$rental['status'])),
                            } ?></td>
                        <td><div class="receipt-actions">
                            <?php if (in_array((string)$rental['status'], ['active', 'returned', 'completed'], true)): ?>
                                <a class="button-link btn-green" href="receipt.php?rental_id=<?= (int)$rental['rental_id'] ?>">Download PDF</a>
                            <?php endif; ?>
                            <?php if ($rental['status'] === 'payment_pending'): ?>
                                <span class="badge pending">Waiting for customer payment</span>
                                <form method="post" action="customers.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="cancel_rental">
                                    <input type="hidden" name="rental_id" value="<?= (int)$rental['rental_id'] ?>">
                                    <button type="submit" class="btn-red">Cancel request</button>
                                </form>
                            <?php elseif ($rental['status'] === 'payment_submitted'): ?>
                                <form method="post" action="customers.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="approve_rental">
                                    <input type="hidden" name="rental_id" value="<?= (int)$rental['rental_id'] ?>">
                                    <button type="submit" class="btn-blue">Approve payment &amp; rental</button>
                                </form>
                                <form method="post" action="customers.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="cancel_rental">
                                    <input type="hidden" name="rental_id" value="<?= (int)$rental['rental_id'] ?>">
                                    <button type="submit" class="btn-red">Reject &amp; cancel</button>
                                </form>
                            <?php elseif ($rental['status'] === 'active'): ?>
                                <form method="post" action="customers.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="complete_rental">
                                    <input type="hidden" name="rental_id" value="<?= (int)$rental['rental_id'] ?>">
                                    <button type="submit" class="btn-blue">Mark returned</button>
                                </form>
                            <?php endif; ?>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
