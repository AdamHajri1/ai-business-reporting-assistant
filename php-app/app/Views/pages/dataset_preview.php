<?php
/** @var array{id: int, name: string, email: string, role: string} $user */
/** @var string $csrfToken */
/** @var array<string, mixed> $dataset */
/** @var array<string, mixed> $validation */

$isValid = (bool) ($validation['is_valid'] ?? false);
$missingColumns = is_array($validation['missing_columns'] ?? null) ? $validation['missing_columns'] : [];
$rowErrors = is_array($validation['row_errors'] ?? null) ? $validation['row_errors'] : [];
$previewRows = is_array($validation['preview'] ?? null) ? $validation['preview'] : [];
$status = (string) $dataset['status'];

$pageTitle = I18n::t('preview.title');
require dirname(__DIR__) . '/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1><?= htmlspecialchars((string) $dataset['original_filename'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="subtitle">
            <?= htmlspecialchars(I18n::t('preview.status'), ENT_QUOTES, 'UTF-8') ?>:
            <span class="badge badge-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </p>
    </div>
    <div class="actions">
        <a href="/" class="btn btn-secondary"><?= htmlspecialchars(I18n::t('preview.back_dashboard'), ENT_QUOTES, 'UTF-8') ?></a>
        <?php if ($status === 'ready'): ?>
            <a href="/datasets/analyze?id=<?= urlencode((string) $dataset['id']) ?>" class="btn btn-primary"><?= htmlspecialchars(I18n::t('preview.open_analytics'), ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
    </div>
</div>

<section class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label"><?= htmlspecialchars(I18n::t('preview.total_rows'), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-value"><?= number_format((int) ($validation['row_count'] ?? 0)) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label"><?= htmlspecialchars(I18n::t('preview.valid_rows'), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-value"><?= number_format((int) ($validation['valid_row_count'] ?? 0)) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label"><?= htmlspecialchars(I18n::t('preview.error_rows'), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-value"><?= number_format((int) ($validation['error_row_count'] ?? 0)) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label"><?= htmlspecialchars(I18n::t('preview.date_range'), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-value" style="font-size:1.05rem;padding-top:0.35rem;">
            <?= htmlspecialchars((string) ($validation['date_min'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
            →
            <?= htmlspecialchars((string) ($validation['date_max'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
</section>

<?php if ($missingColumns !== []): ?>
    <div class="alert alert-error" role="alert">
        <strong><?= htmlspecialchars(I18n::t('preview.missing_columns'), ENT_QUOTES, 'UTF-8') ?></strong>
        <?= htmlspecialchars(implode(', ', $missingColumns), ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($rowErrors !== []): ?>
    <section class="card" role="alert">
        <h2><?= htmlspecialchars(I18n::t('preview.validation_errors', ['count' => count($rowErrors)]), ENT_QUOTES, 'UTF-8') ?></h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= htmlspecialchars(I18n::t('preview.csv_line'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('preview.reason'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rowErrors as $err): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars(I18n::t('preview.line_num', ['num' => (int) ($err['row'] ?? 0)]), ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars((string) ($err['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if ($previewRows !== []): ?>
    <section class="card">
        <h2><?= htmlspecialchars(I18n::t('preview.valid_preview', ['count' => count($previewRows)]), ENT_QUOTES, 'UTF-8') ?></h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= htmlspecialchars(I18n::t('preview.col_date'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('preview.col_order_id'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('preview.col_product'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('preview.col_category'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('preview.col_quantity'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('preview.col_unit_price'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previewRows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($row['date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['order_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['product'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['quantity'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>$<?= number_format((float) ($row['unit_price'] ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<section class="card">
    <?php if ($isValid && $status === 'validated'): ?>
        <form method="POST" action="/datasets/confirm" class="actions">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="dataset_id" value="<?= htmlspecialchars((string) $dataset['id'], ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-primary"><?= htmlspecialchars(I18n::t('preview.confirm_btn'), ENT_QUOTES, 'UTF-8') ?></button>
            <span class="subtitle" style="margin:0;"><?= htmlspecialchars(I18n::t('preview.confirm_hint'), ENT_QUOTES, 'UTF-8') ?></span>
        </form>
    <?php elseif ($status === 'ready'): ?>
        <div class="alert alert-success" role="status" style="margin:0;">
            <?= htmlspecialchars(I18n::t('preview.ready_msg'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php else: ?>
        <div class="alert alert-error" role="alert" style="margin:0;">
            <?= htmlspecialchars(I18n::t('preview.rejected_msg'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>