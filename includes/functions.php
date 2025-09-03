<?php
function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function compute_age_from_year(int $year): int {
    $currentYear = (int)date('Y');
    if ($year <= 0 || $year > $currentYear) return 0;
    return $currentYear - $year;
}

function is_valid_year($year): bool {
    return is_numeric($year) && (int)$year >= 1900 && (int)$year <= (int)date('Y');
}

function base_url($path = ''): string {
    $base = defined('BASE_URL') ? BASE_URL : '/';
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function sex_badge(string $sex): string {
    if ($sex === 'M') {
        $label = 'Mâle';
        $cls = 'badge badge-male';
    } elseif ($sex === 'F') {
        $label = 'Femelle';
        $cls = 'badge badge-female';
    } else {
        $label = 'Indéfini';
        $cls = 'badge badge-undefined'; // Vous pouvez créer cette classe CSS
    }
    return '<span class="' . $cls . '">' . $label . '</span>';
}
