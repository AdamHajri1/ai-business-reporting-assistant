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
    <form method="POST" action="/datasets/upload" enctype="multipart/form-data" class="upload-form">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input id="csv_file" name="csv_file" type="file" accept=".csv,text/csv" required class="file-input-hidden">
        <label for="csv_file" class="file-dropzone" id="csvDropzone"
               data-default-title="<?= htmlspecialchars(I18n::t('dashboard.upload.drop_title'), ENT_QUOTES, 'UTF-8') ?>"
               data-default-meta="<?= htmlspecialchars(I18n::t('dashboard.upload.no_file'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="file-dropzone-icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
            </span>
            <span class="file-dropzone-text">
                <span class="file-dropzone-title" id="csvFileTitle"><?= htmlspecialchars(I18n::t('dashboard.upload.drop_title'), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="file-dropzone-meta" id="csvFileMeta"><?= htmlspecialchars(I18n::t('dashboard.upload.no_file'), ENT_QUOTES, 'UTF-8') ?></span>
            </span>
            <span class="btn btn-secondary btn-sm file-dropzone-browse"><?= htmlspecialchars(I18n::t('dashboard.upload.browse'), ENT_QUOTES, 'UTF-8') ?></span>
        </label>
        <button type="submit" class="btn btn-primary upload-submit"><?= htmlspecialchars(I18n::t('dashboard.upload.button'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</section>

<section class="card" data-empty-message="<?= htmlspecialchars(I18n::t('dashboard.datasets.empty'), ENT_QUOTES, 'UTF-8') ?>">
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
                                    <form method="POST" action="/datasets/delete" style="margin:0;"
                                          data-ajax-delete="tr"
                                          data-confirm-title="<?= htmlspecialchars(I18n::t('dataset.delete_modal_title'), ENT_QUOTES, 'UTF-8') ?>"
                                          data-confirm="<?= htmlspecialchars(I18n::t('dataset.delete_confirm', ['name' => (string) $dataset['original_filename']]), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="dataset_id" value="<?= htmlspecialchars((string) $dataset['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <?= htmlspecialchars(I18n::t('dataset.delete_btn'), ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card" id="reportsCard" data-empty-message="<?= htmlspecialchars(I18n::t('dashboard.reports.empty'), ENT_QUOTES, 'UTF-8') ?>">
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
                        <tr data-dataset-id="<?= htmlspecialchars((string) $report['dataset_id'], ENT_QUOTES, 'UTF-8') ?>">
                            <td><strong>#<?= (int) $report['id'] ?></strong></td>
                            <td><?= htmlspecialchars((string) $report['original_filename'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $report['period_start'] . ' → ' . (string) $report['period_end'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge badge-validated"><?= htmlspecialchars((string) $report['ai_model'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars((string) $report['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-secondary btn-sm" href="/reports/show?id=<?= (int) $report['id'] ?>">
                                        <?= htmlspecialchars(I18n::t('dashboard.reports.action.view'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <form method="POST" action="/reports/delete" style="margin:0;"
                                          data-ajax-delete="tr"
                                          data-confirm="<?= htmlspecialchars(I18n::t('report.delete_confirm', ['id' => (int) $report['id']]), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <?= htmlspecialchars(I18n::t('report.delete_btn'), ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>