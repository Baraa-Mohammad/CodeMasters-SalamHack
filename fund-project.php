<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['funder']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('marketplace.php');
}

$projectId = (int) ($_POST['project_id'] ?? 0);
$amount = (float) ($_POST['amount'] ?? 0);

if ($amount <= 0) {
    flash('error', 'يرجى إدخال مبلغ صحيح');
    redirect('project-details.php?id=' . $projectId);
}

try {
    db()->beginTransaction();

    $stmt = db()->prepare('SELECT * FROM projects WHERE id = ? FOR UPDATE');
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();

    if (!$project || !in_array($project['status'], ['approved', 'funded'], true)) {
        throw new RuntimeException('project_unavailable');
    }

    $newFunding = (float) $project['current_funding'] + $amount;
    $newStatus = $newFunding >= (float) $project['funding_goal'] ? 'funded' : 'approved';

    $insert = db()->prepare('INSERT INTO funding_transactions (project_id, funder_id, amount) VALUES (?, ?, ?)');
    $insert->execute([$projectId, (int) $user['id'], $amount]);

    $updateProject = db()->prepare('UPDATE projects SET current_funding = ?, status = ? WHERE id = ?');
    $updateProject->execute([$newFunding, $newStatus, $projectId]);

    ensure_wallet((int) $project['owner_id']);
    $updateWallet = db()->prepare('UPDATE wallets SET balance = balance + ? WHERE user_id = ?');
    $updateWallet->execute([$amount, (int) $project['owner_id']]);

    db()->commit();
    flash('success', 'تم التمويل بنجاح');
} catch (Throwable $exception) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash('error', 'تعذر إتمام التمويل');
}

redirect('project-details.php?id=' . $projectId);
