<?php
/** @var array{id: int, name: string, email: string, role: string} $user */
/** @var string $csrfToken */
/** @var array<int, array<string, mixed>> $datasets */
/** @var array<int, array<string, mixed>> $reports */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */

$pageTitle = I18n::t('dashboard.title');
require dirname(__DIR__) . '/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1><?= htmlspecialchars(I18n::t('dashboard.title'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="subtitle"><?= htmlspecialchars(I18n::t('dashboard.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</div>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($errorMessage !== null): ?>
    <div class="alert alert-error" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card">
    <h2><?= htmlspecialchars(I18n::t('dashboard.upload.heading'), ENT_QUOTES, 'UTF-8') ?></h2>
    <form method="POST" action="/datasets/upload" enctype="multipart/form-data" class="inline-form">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label for="csv_file"><?= htmlspecialchars(I18n::t('dashboard.upload.label'), ENT_QUOTES, 'UTF-8') ?></label>
            <input id="csv_file" name="csv_file" type="file" accept=".csv,text/csv" required>
        </div>
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars(I18n::t('dashboard.upload.button'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</section>

<section class="card">
    <h2><?= htmlspecialchars(I18n::t('dashboard.datasets.heading'), ENT_QUOTES, 'UTF-8') ?></h2>

    <?php if ($datasets === []): ?>
        <p class="subtitle"><?= htmlspecialchars(I18n::t('dashboard.datasets.empty'), ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= htmlspecialchars(I18n::t('dashboard.datasets.col.filename'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('dashboard.datasets.col.size'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('dashboard.datasets.col.rows'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('dashboard.datasets.col.date_range'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('dashboard.datasets.col.status'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('dashboard.datasets.col.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datasets as $dataset): ?>
                        <?php $status = (string) $dataset['status']; ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string) $dataset['original_filename'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars(number_format(((int) $dataset['file_size_bytes']) / 1024, 1) . ' KB', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $dataset['valid_row_count'] ?> / <?= (int) $dataset['row_count'] ?></td>
                            <td>
                                <?php if (!empty($dataset['date_min']) && !empty($dataset['date_max'])): ?>
                                    <?= htmlspecialchars((string) $dataset['date_min'] . ' → ' . (string) $dataset['date_max'], ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars(I18n::t('status.' . $status), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-secondary btn-sm" href="/datasets/preview?id=<?= urlencode((string) $dataset['id']) ?>">
                                        <?= htmlspecialchars($status === 'ready' ? I18n::t('dashboard.datasets.action.preview') : I18n::t('dashboard.datasets.action.validate'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <?php if ($status === 'ready'): ?>
                                        <a class="btn btn-primary btn-sm" href="/datasets/analyze?id=<?= urlencode((string) $dataset['id']) ?>">
                                            <?= htmlspecialchars(I18n::t('dashboard.datasets.action.analytics'), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <h2><?= htmlspecialchars(I18n::t('dashboard.reports.heading'), ENT_QUOTES, 'UTF-8') ?></h2>

    <?php if ($reports === []): ?>
        <p class="subtitle"><?= htmlspecialchars(I18n::t('dashboard.reports.empty'), ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= htmlspecialchars(I18n::t('dashboard.reports.col.report'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('dashboard.reports.col.dataset'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('dashboard.reports.col.period'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('dashboard.reports.col.engine'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('dashboard.reports.col.created_at'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(I18n::t('dashboard.reports.col.action'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><strong>#<?= (int) $report['id'] ?></strong></td>
                            <td><?= htmlspecialchars((string) $report['original_filename'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $report['period_start'] . ' → ' . (string) $report['period_end'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge badge-validated"><?= htmlspecialchars((string) $report['ai_model'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars((string) $report['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a class="btn btn-secondary btn-sm" href="/reports/show?id=<?= (int) $report['id'] ?>">
                                    <?= htmlspecialchars(I18n::t('dashboard.reports.action.view'), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>