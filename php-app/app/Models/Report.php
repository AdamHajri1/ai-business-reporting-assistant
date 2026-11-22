<?php

declare(strict_types=1);

final class Report
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(
        int $userId,
        string $datasetId,
        string $periodStart,
        string $periodEnd,
        string $metricsJson,
        string $aiSummary,
        string $aiModel,
        string $promptVersion,
        ?string $compareStart = null,
        ?string $compareEnd = null
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO reports (
                user_id, dataset_id, period_start, period_end,
                compare_start, compare_end, metrics_json, ai_summary,
                ai_model, prompt_version
             ) VALUES (
                :user_id, :dataset_id, :period_start, :period_end,
                :compare_start, :compare_end, :metrics_json, :ai_summary,
                :ai_model, :prompt_version
             )'
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':dataset_id' => $datasetId,
            ':period_start' => $periodStart,
            ':period_end' => $periodEnd,
            ':compare_start' => $compareStart,
            ':compare_end' => $compareEnd,
            ':metrics_json' => $metricsJson,
            ':ai_summary' => $aiSummary,
            ':ai_model' => $aiModel,
            ':prompt_version' => $promptVersion,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByIdForUser(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.user_id, r.dataset_id, r.period_start, r.period_end,
                    r.compare_start, r.compare_end, r.metrics_json, r.ai_summary,
                    r.ai_model, r.prompt_version, r.created_at,
                    d.original_filename
             FROM reports r
             INNER JOIN datasets d ON d.id = r.dataset_id
             WHERE r.id = :id AND r.user_id = :user_id
             LIMIT 1'
        );

        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
        ]);

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.dataset_id, r.period_start, r.period_end,
                    r.ai_model, r.prompt_version, r.created_at,
                    d.original_filename
             FROM reports r
             INNER JOIN datasets d ON d.id = r.dataset_id
             WHERE r.user_id = :user_id
             ORDER BY r.created_at DESC'
        );

        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    }
}