<?php
declare(strict_types=1);

use Cake\Datasource\ConnectionManager;

require dirname(__DIR__) . '/config/bootstrap.php';

$conn = ConnectionManager::get('default');
$ts = date('Ymd_His');
$backupTable = 'eventsubmissions_dedupe_backup_' . $ts;
$mapTable = 'eventsubmissions_dedupe_map_' . $ts;

$hasContentSql = "(
    COALESCE(NULLIF(TRIM(es.mediafile_file_system_name), ''), '') <> '' OR
    COALESCE(NULLIF(TRIM(es.report), ''), '') <> '' OR
    COALESCE(NULLIF(TRIM(es.score_sheet), ''), '') <> '' OR
    COALESCE(NULLIF(TRIM(es.additional_documents), ''), '') <> '' OR
    COALESCE(NULLIF(TRIM(es.context_box), ''), '') <> ''
)";

$sql = "
SELECT
    es.id,
    es.conventionseason_id,
    es.convention_id,
    es.season_id,
    es.season_year,
    es.event_id,
    es.student_id,
    CASE WHEN je.eventsubmission_id IS NOT NULL OR rp.eventsubmission_id IS NOT NULL THEN 1 ELSE 0 END AS has_ref,
    CASE WHEN $hasContentSql THEN 1 ELSE 0 END AS has_content
FROM eventsubmissions es
INNER JOIN (
    SELECT conventionseason_id, convention_id, season_id, season_year, event_id, student_id
    FROM eventsubmissions
    WHERE student_id > 0
    GROUP BY conventionseason_id, convention_id, season_id, season_year, event_id, student_id
    HAVING COUNT(*) > 1
) d ON d.conventionseason_id = es.conventionseason_id
   AND d.convention_id = es.convention_id
   AND d.season_id = es.season_id
   AND d.season_year = es.season_year
   AND d.event_id = es.event_id
   AND d.student_id = es.student_id
LEFT JOIN (
    SELECT DISTINCT eventsubmission_id
    FROM judgeevaluations
) je ON je.eventsubmission_id = es.id
LEFT JOIN (
    SELECT DISTINCT eventsubmission_id
    FROM resultpositions
) rp ON rp.eventsubmission_id = es.id
ORDER BY
    es.conventionseason_id,
    es.convention_id,
    es.season_id,
    es.season_year,
    es.event_id,
    es.student_id,
    has_ref DESC,
    has_content DESC,
    es.id DESC
";

$rows = $conn->execute($sql)->fetchAll('assoc');
if (empty($rows)) {
    echo "No duplicate student-event submission keys found.\n";
    exit(0);
}

$groups = [];
foreach ($rows as $row) {
    $key = implode('|', [
        (int)$row['conventionseason_id'],
        (int)$row['convention_id'],
        (int)$row['season_id'],
        (string)$row['season_year'],
        (int)$row['event_id'],
        (int)$row['student_id'],
    ]);
    if (!isset($groups[$key])) {
        $groups[$key] = [];
    }
    $groups[$key][] = $row;
}

$deleteIds = [];
$mapping = [];
$groupsTouched = 0;
foreach ($groups as $key => $groupRows) {
    if (count($groupRows) < 2) {
        continue;
    }
    $groupsTouched++;
    $keepId = (int)$groupRows[0]['id'];
    foreach (array_slice($groupRows, 1) as $row) {
        $deleteId = (int)$row['id'];
        $deleteIds[] = $deleteId;
        $mapping[] = [
            'delete_id' => $deleteId,
            'keep_id' => $keepId,
            'dedupe_key' => $key,
            'delete_has_ref' => (int)$row['has_ref'],
            'delete_has_content' => (int)$row['has_content'],
            'keep_has_ref' => (int)$groupRows[0]['has_ref'],
            'keep_has_content' => (int)$groupRows[0]['has_content'],
        ];
    }
}

$deleteIds = array_values(array_unique($deleteIds));
if (empty($deleteIds)) {
    echo "No duplicate rows to delete after ranking.\n";
    exit(0);
}

$conn->begin();
try {
    $conn->execute("CREATE TABLE `$backupTable` LIKE `eventsubmissions`");
    $conn->execute("CREATE TABLE `$mapTable` (
        `delete_id` INT NOT NULL,
        `keep_id` INT NOT NULL,
        `dedupe_key` VARCHAR(255) NOT NULL,
        `delete_has_ref` TINYINT NOT NULL,
        `delete_has_content` TINYINT NOT NULL,
        `keep_has_ref` TINYINT NOT NULL,
        `keep_has_content` TINYINT NOT NULL,
        PRIMARY KEY (`delete_id`),
        KEY `idx_keep_id` (`keep_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $chunkSize = 1000;
    $chunks = array_chunk($deleteIds, $chunkSize);
    foreach ($chunks as $chunk) {
        $idList = implode(',', array_map('intval', $chunk));
        $conn->execute("INSERT INTO `$backupTable` SELECT * FROM `eventsubmissions` WHERE `id` IN ($idList)");
    }

    foreach ($mapping as $m) {
        $conn->execute(
            "INSERT INTO `$mapTable` (delete_id, keep_id, dedupe_key, delete_has_ref, delete_has_content, keep_has_ref, keep_has_content)
             VALUES (:delete_id, :keep_id, :dedupe_key, :delete_has_ref, :delete_has_content, :keep_has_ref, :keep_has_content)",
            [
                'delete_id' => $m['delete_id'],
                'keep_id' => $m['keep_id'],
                'dedupe_key' => $m['dedupe_key'],
                'delete_has_ref' => $m['delete_has_ref'],
                'delete_has_content' => $m['delete_has_content'],
                'keep_has_ref' => $m['keep_has_ref'],
                'keep_has_content' => $m['keep_has_content'],
            ]
        );
    }

    $judgeUpdate = $conn->execute(
        "UPDATE `judgeevaluations` j
         INNER JOIN `$mapTable` m ON m.delete_id = j.eventsubmission_id
         SET j.eventsubmission_id = m.keep_id"
    );
    $resultUpdate = $conn->execute(
        "UPDATE `resultpositions` r
         INNER JOIN `$mapTable` m ON m.delete_id = r.eventsubmission_id
         SET r.eventsubmission_id = m.keep_id"
    );

    foreach ($chunks as $chunk) {
        $idList = implode(',', array_map('intval', $chunk));
        $conn->execute("DELETE FROM `eventsubmissions` WHERE `id` IN ($idList)");
    }

    $conn->commit();

    $leftDuplicates = $conn->execute(
        "SELECT COUNT(*) AS c FROM (
            SELECT 1
            FROM eventsubmissions
            WHERE student_id > 0
            GROUP BY conventionseason_id, convention_id, season_id, season_year, event_id, student_id
            HAVING COUNT(*) > 1
        ) t"
    )->fetch('assoc');

    echo "Cleanup complete.\n";
    echo "Groups processed: " . $groupsTouched . "\n";
    echo "Rows deleted: " . count($deleteIds) . "\n";
    echo "Judgeevaluations remapped: " . $judgeUpdate->rowCount() . "\n";
    echo "Resultpositions remapped: " . $resultUpdate->rowCount() . "\n";
    echo "Backup table: $backupTable\n";
    echo "Mapping table: $mapTable\n";
    echo "Duplicate keys remaining: " . (int)($leftDuplicates['c'] ?? 0) . "\n";
} catch (\Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, "Cleanup failed, transaction rolled back: " . $e->getMessage() . "\n");
    exit(1);
}
