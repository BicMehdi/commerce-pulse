<?php
declare(strict_types=1);

function env(string $key, string $default = ''): string { $value = getenv($key); return $value === false ? $default : $value; }
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function euros(int $cents): string { return number_format($cents / 100, 2, ',', ' ') . ' €'; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(App\Core\Session::csrf()) . '">'; }
function selected(mixed $a, mixed $b): string { return (string)$a === (string)$b ? 'selected' : ''; }
function status_label(string $status): string { return ['pending'=>'À préparer', 'processing'=>'En préparation', 'shipped'=>'Expédiée', 'cancelled'=>'Annulée'][$status] ?? $status; }
function short_date(string $date): string { return (new DateTimeImmutable($date))->format('d/m/Y'); }
function icon(string $name, string $class = ''): string {
    $paths = [
        'bag'=>'<path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
        'grid'=>'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'chart'=>'<path d="M4 3v17h17M8 15v-4m5 4V7m5 8V4"/>',
        'box'=>'<path d="m12 3 9 5-9 5-9-5 9-5Zm-9 5v9l9 5 9-5V8M12 13v9M7 5l9 5"/>',
        'arrow'=>'<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'check'=>'<path d="m5 12 4 4L19 6"/>',
        'search'=>'<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>',
        'leaf'=>'<path d="M20 3C8 2 2 9 6 16s16 2 14-13ZM5 20 17 7"/>',
    ];
    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['box']) . '</svg>';
}
