<div class="page-heading">
    <div><p class="eyebrow">ACCESS CONTROL</p><h1>Staff users</h1></div>
    <p>Only administrators can create or manage staff accounts. New staff must change their temporary password after signing in.</p>
</div>
<?php if (is_array($notice)): ?><div class="<?= e($notice[0]) ?>" role="status"><?= e($notice[1]) ?></div><?php endif; ?>

<section class="customer-card">
    <h2>Register a staff account</h2>
    <form method="post" action="staff.php" class="form-inline">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="role" value="staff">
        <label>Full name<input type="text" name="full_name" maxlength="100" required></label>
        <label>Email<input type="email" name="email" maxlength="100" required></label>
        <label>Temporary password (12+ characters)<input type="password" name="password" minlength="12" autocomplete="new-password" required></label>
        <button type="submit" class="btn-green">Create staff account</button>
    </form>
</section>

<h2>Registered staff accounts</h2>
<div class="table-scroll"><table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>First login</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= e($user['full_name']) ?></td>
            <td><?= e($user['email']) ?></td>
            <td><?= e(ucfirst((string)$user['role'])) ?></td>
            <td><?= e(date('d M Y', strtotime((string)$user['created_at']))) ?></td>
            <td><?= (int)$user['must_change_password'] === 1 ? 'Password change required' : 'Ready' ?></td>
            <td><div class="receipt-actions">
                <details class="customer-edit-details">
                    <summary class="button-ghost">Edit</summary>
                    <form method="post" action="staff.php" class="customer-edit-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">
                        <label>Full name<input type="text" name="full_name" maxlength="100" value="<?= e($user['full_name']) ?>" required></label>
                        <label>Email<input type="email" name="email" maxlength="100" value="<?= e($user['email']) ?>" required></label>
                        <label>Role<select name="role">
                            <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                        </select></label>
                        <button class="btn-blue" type="submit">Save account</button>
                    </form>
                </details>
                <?php if ((int)$user['user_id'] !== (int)$_SESSION['user_id']): ?>
                    <form method="post" action="staff.php" onsubmit="return confirm('Delete this staff account?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">
                        <button type="submit" class="btn-red">Delete</button>
                    </form>
                <?php else: ?><span class="muted">Current account</span><?php endif; ?>
            </div></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
