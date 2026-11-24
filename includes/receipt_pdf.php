<?php

function rental_pdf_escape(string $value): string {
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) $value = $converted;
    }
    $value = preg_replace('/[^\x20-\x7E]/', '?', $value);
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
}

function rental_pdf_text(string &$content, string $text, int $x, int $y, int $size = 11, string $font = 'F1'): void {
    $content .= "BT /{$font} {$size} Tf {$x} {$y} Td (" . rental_pdf_escape($text) . ") Tj ET\n";
}

function rental_pdf_rule(string &$content, int $y): void {
    $content .= "0.65 w 54 {$y} m 541 {$y} l S\n";
}

function rental_receipt_calendar_date(string $value): string {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
        return $value;
    }
    return $date->format('d M Y');
}

function rental_make_receipt_pdf(array $rental): string {
    $start = DateTime::createFromFormat('!Y-m-d H:i', (string)$rental['start_date'] . ' ' . substr((string)$rental['start_time'], 0, 5));
    $end = DateTime::createFromFormat('!Y-m-d H:i', (string)$rental['end_date'] . ' ' . substr((string)$rental['return_time'], 0, 5));
    $billableDays = ($start && $end && $end > $start)
        ? max(1, (int)ceil(($end->getTimestamp() - $start->getTimestamp()) / 86400))
        : 1;
    $content = "0.12 0.18 0.23 rg\n";
    rental_pdf_text($content, 'SAFE DRIVING  CAR RENTALS', 54, 785, 19, 'F2');
    $content .= "0 0 0 rg\n";
    rental_pdf_text($content, 'RENTAL RECEIPT', 54, 756, 12, 'F2');
    rental_pdf_text($content, 'Receipt: R-' . str_pad((string)$rental['rental_id'], 6, '0', STR_PAD_LEFT), 54, 732, 10);
    rental_pdf_text($content, 'Issued at: ' . date('Y-m-d H:i'), 365, 732, 10);
    rental_pdf_rule($content, 716);

    rental_pdf_text($content, 'CUSTOMER', 54, 694, 10, 'F2');
    rental_pdf_text($content, (string)$rental['full_name'], 54, 675, 12, 'F2');
    rental_pdf_text($content, (string)$rental['email'], 54, 657, 10);
    rental_pdf_text($content, 'Phone: ' . (string)$rental['phone'], 54, 640, 10);

    rental_pdf_text($content, 'VEHICLE', 54, 606, 10, 'F2');
    rental_pdf_text($content, (string)$rental['model'] . ' (' . (string)$rental['type'] . ')', 54, 587, 12, 'F2');
    rental_pdf_text($content, 'Registration: ' . (string)$rental['plate_no'], 54, 569, 10);

    rental_pdf_text($content, 'RENTAL PERIOD', 54, 535, 10, 'F2');
    rental_pdf_text($content, 'Pick-up: ' . rental_receipt_calendar_date((string)$rental['start_date']) . ' ' . substr((string)$rental['start_time'], 0, 5), 54, 516, 11);
    rental_pdf_text($content, 'Return by: ' . rental_receipt_calendar_date((string)$rental['end_date']) . ' ' . substr((string)$rental['return_time'], 0, 5), 54, 497, 11);
    rental_pdf_text($content, 'Status: ' . ucfirst((string)$rental['status']), 54, 478, 10);

    rental_pdf_rule($content, 452);
    rental_pdf_text($content, 'FEE SUMMARY', 54, 431, 10, 'F2');
    rental_pdf_text($content, 'Daily rate', 54, 407, 11);
    rental_pdf_text($content, '$' . number_format((float)$rental['daily_rate'], 2), 430, 407, 11);
    $rentalSubtotal = round((float)$rental['total_cost'] + (float)($rental['discount_amount'] ?? 0), 2);
    $discountPercent = (int)($rental['discount_percent'] ?? 0);
    $discountAmount = round((float)($rental['discount_amount'] ?? 0), 2);
    rental_pdf_text($content, 'Rental subtotal (' . $billableDays . ' billable day' . ($billableDays === 1 ? '' : 's') . ')', 54, 384, 11);
    rental_pdf_text($content, '$' . number_format($rentalSubtotal, 2), 430, 384, 11);
    if ($discountAmount > 0) {
        rental_pdf_text($content, 'Loyalty reward (' . $discountPercent . '%)', 54, 362, 11);
        rental_pdf_text($content, '-$' . number_format($discountAmount, 2), 430, 362, 11);
    }
    $content .= "0.12 0.18 0.23 rg\n";
    rental_pdf_text($content, 'TOTAL: $' . number_format((float)$rental['total_cost'], 2), 54, 337, 17, 'F2');
    $content .= "0 0 0 rg\n";
    rental_pdf_rule($content, 314);
    rental_pdf_text($content, 'Please keep this receipt for your records.', 54, 300, 10);
    rental_pdf_text($content, 'Thank you for choosing safe driving car rentals.', 54, 283, 10);
    rental_pdf_text($content, 'Contact: bafidele2024@gmail.com', 54, 266, 9);

    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /consolas-bold >>',
        '<< /Type /Font /Subtype /Type2 /BaseFont /Helvetica-Bold >>',
        '<< /Length ' . strlen($content) . ">>\nstream\n" . $content . "endstream",
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $objectNumber = $index + 1;
        $offsets[] = strlen($pdf);
        $pdf .= $objectNumber . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";
    return $pdf;
}
