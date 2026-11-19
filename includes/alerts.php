<?php
/**
 * Global Flash Message Alert Component
 */
$flash = getFlash();
if ($flash):
    $alertType = $flash['type'] ?? 'info';
    $message = $flash['message'] ?? '';
    $iconMap = [
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info'    => 'bi-info-circle-fill'
    ];
    $icon = $iconMap[$alertType] ?? 'bi-info-circle-fill';
?>
<div class="alert alert-<?= e($alertType) ?> alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
    <i class="bi <?= e($icon) ?> fs-5 me-2"></i>
    <div class="flex-grow-1"><?= e($message) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
