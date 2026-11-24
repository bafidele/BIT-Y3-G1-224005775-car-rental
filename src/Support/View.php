<?php
declare(strict_types=1);

namespace CarRental\Support;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $file = dirname(__DIR__) . '/Views/' . $template . '.php';
        if (!is_file($file)) {
            http_response_code(500);
            exit('Page template is unavailable.');
        }

        extract($data, EXTR_SKIP);
        include dirname(__DIR__, 2) . '/includes/header.php';
        include $file;
        include dirname(__DIR__, 2) . '/includes/footer.php';
    }
}
