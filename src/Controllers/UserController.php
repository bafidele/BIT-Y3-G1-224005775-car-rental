<?php
declare(strict_types=1);

namespace CarRental\Controllers;

use CarRental\Models\User;
use CarRental\Services\UserValidator;
use CarRental\Support\View;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class UserController
{
    public function __construct(private PDO $pdo) {}

    public function manage(): void
    {
        \requireAdmin();
        $users = new User($this->pdo);
        $notice = $_SESSION['staff_notice'] ?? null;
        unset($_SESSION['staff_notice']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = ['error', 'The staff action could not be completed.'];
            try {
                if (!\verifyCsrfToken()) throw new RuntimeException('Your session expired. Please reload and try again.');
                $action = (string)($_POST['action'] ?? '');
                $id = (int)($_POST['user_id'] ?? 0);
                if ($action === 'create') {
                    [$data, $errors] = UserValidator::validate($_POST, true);
                    if ($errors) throw new RuntimeException(implode(' ', $errors));
                    $users->createStaff($data);
                    $message = ['success', 'Staff account created. The new staff member must change the temporary password at first login.'];
                } elseif ($action === 'update') {
                    [$data, $errors] = UserValidator::validate($_POST, false);
                    if ($errors) throw new RuntimeException(implode(' ', $errors));
                    if ($id === (int)$_SESSION['user_id'] && $data['role'] !== 'admin') {
                        throw new RuntimeException('You cannot remove administrator access from the account you are using.');
                    }
                    $users->update($id, $data);
                    $message = ['success', 'Staff account updated.'];
                } elseif ($action === 'delete') {
                    $users->delete($id, (int)$_SESSION['user_id']);
                    $message = ['success', 'Staff account deleted.'];
                } else {
                    throw new RuntimeException('Choose a valid staff action.');
                }
            } catch (RuntimeException $exception) {
                $message = ['error', $exception->getMessage()];
            } catch (PDOException $exception) {
                error_log('Staff management database error: ' . $exception->getMessage());
                $message = ['error', $exception->getCode() === '23000' ? 'That email is already in use.' : 'The staff account could not be saved.'];
            } catch (Throwable $exception) {
                error_log('Staff management failed: ' . $exception->getMessage());
                $message = ['error', 'The staff account could not be saved. Please try again.'];
            }
            $_SESSION['staff_notice'] = $message;
            header('Location: staff.php', true, 303);
            exit;
        }

        View::render('users/manage', ['title' => 'Staff users', 'users' => $users->all(), 'notice' => $notice]);
    }
}
