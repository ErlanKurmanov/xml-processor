<?php
require_once __DIR__ . '/src/Services/FamilyService.php';
require_once __DIR__ . '/src/Database.php';

echo "Worker started...\n";

$db = new Database();
$service = new FamilyService();
$pdo = $db->getPdo();

while (true) {
    $stmt = $pdo->query("SELECT id, input_file FROM tasks WHERE status = 'PENDING' LIMIT 1 FOR UPDATE SKIP LOCKED");
    $task = $stmt->fetch();

    if ($task) {
        echo "Processing Task ID: {$task['id']}...\n";

        $pdo->prepare("UPDATE tasks SET status = 'PROCESSING' WHERE id = ?")->execute([$task['id']]);

        try {
            $xmlResult = $service->processLogic($task['input_file'], $task['id']);

            $upd = $pdo->prepare("UPDATE tasks SET status = 'DONE', result_xml = ? WHERE id = ?");
            $upd->execute([$xmlResult, $task['id']]);
            echo "Task {$task['id']} Done.\n";

        } catch (Exception $e) {
            $upd = $pdo->prepare("UPDATE tasks SET status = 'ERROR', error_message = ? WHERE id = ?");
            $upd->execute([$e->getMessage(), $task['id']]);
            echo "Task {$task['id']} Failed: " . $e->getMessage() . "\n";
        }
    } else {
        sleep(2);
    }
}