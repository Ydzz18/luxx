<?php
require_once 'auth.php';
requireLogin();
require_once '../Feedback.php';

// Check permission
if (!canDo('feedback', 'view')) {
    showAccessDenied();
}

$feedbackModel = new Feedback();
$counts = $feedbackModel->getCounts();
$statusFilter = $_GET['status'] ?? null;
$feedbacks = $feedbackModel->getAll($statusFilter);

$message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $feedbackModel->updateStatus($_POST['feedback_id'], $_POST['status'], $_POST['admin_notes']);
    redirect('feedback.php?updated=1');
}

// Handle delete
if (isset($_GET['delete'])) {
    $feedbackModel->delete($_GET['delete']);
    redirect('feedback.php?deleted=1');
}

if (isset($_GET['updated'])) $message = 'Feedback updated successfully!';
if (isset($_GET['deleted'])) $message = 'Feedback deleted successfully!';

// View single feedback
$viewFeedback = null;
if (isset($_GET['id'])) {
    $viewFeedback = $feedbackModel->getById($_GET['id']);
    // Mark as read if new
    if ($viewFeedback && $viewFeedback['status'] === 'new') {
        $feedbackModel->updateStatus($_GET['id'], 'read', null);
        $viewFeedback['status'] = 'read';
    }
}

$pageTitle = $viewFeedback ? 'Feedback Details' : 'Feedback & Messages';
$pageStyles = '
    .feedback-stats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 25px; }
    .mini-stat { background: #fff; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.3s; border: 2px solid transparent; }
    .mini-stat:hover, .mini-stat.active { border-color: #d4af37; }
    .mini-stat h4 { font-size: 28px; color: #1a1a2e; }
    .mini-stat p { color: #888; font-size: 13px; margin-top: 5px; }
    .mini-stat.new h4 { color: #e74c3c; }
    .filters { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
    .filter-btn { padding: 8px 20px; background: #fff; border: 1px solid #ddd; border-radius: 20px; cursor: pointer; text-decoration: none; color: #666; font-size: 14px; }
    .filter-btn:hover, .filter-btn.active { background: #d4af37; color: #000; border-color: #d4af37; }
    .feedback-item { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: start; }
    .feedback-item.unread { border-left: 4px solid #e74c3c; }
    .feedback-meta { display: flex; gap: 15px; margin-bottom: 10px; font-size: 13px; color: #888; }
    .feedback-meta strong { color: #333; }
    .feedback-subject { font-weight: 600; margin-bottom: 8px; color: #1a1a2e; }
    .feedback-preview { color: #666; font-size: 14px; line-height: 1.5; }
    .feedback-actions { display: flex; gap: 10px; }
    .feedback-actions a { padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 13px; }
    .btn-view { background: #f0f0f0; color: #333; }
    .btn-delete { background: #fee; color: #e74c3c; }
    .feedback-detail { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; }
    .detail-main { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .detail-sidebar { display: flex; flex-direction: column; gap: 20px; }
    .detail-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .detail-card h3 { font-size: 16px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
    .info-row { display: flex; justify-content: space-between; padding: 8px 0; }
    .info-row .label { color: #888; }
    .message-content { background: #f8f9fa; padding: 20px; border-radius: 8px; line-height: 1.7; margin-top: 20px; white-space: pre-wrap; }
    .back-link { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: #666; text-decoration: none; }
    .success-msg { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
    .status-new { background: #fee; color: #e74c3c; }
    .status-read { background: #fff3cd; color: #856404; }
    .status-replied { background: #cce5ff; color: #004085; }
    .status-resolved { background: #d4edda; color: #155724; }
    .empty-state { text-align: center; padding: 60px; color: #888; background: #fff; border-radius: 12px; }
';
include 'includes/header.php';
?>

            <?php if ($message): ?><div class="success-msg"><?= $message ?></div><?php endif; ?>

            <?php if ($viewFeedback): ?>
            <!-- Single Feedback View -->
            <a href="feedback.php" class="back-link">← Back to All Feedback</a>
            
            <div class="feedback-detail">
                <div class="detail-main">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                        <div>
                            <h2 style="margin-bottom: 5px;"><?= htmlspecialchars($viewFeedback['subject'] ?: 'No Subject') ?></h2>
                            <p style="color: #888;">From: <?= htmlspecialchars($viewFeedback['name'] ?: 'Anonymous') ?> &lt;<?= htmlspecialchars($viewFeedback['email']) ?>&gt;</p>
                        </div>
                        <span class="status-badge status-<?= $viewFeedback['status'] ?>"><?= ucfirst($viewFeedback['status']) ?></span>
                    </div>
                    
                    <div class="message-content"><?= nl2br(htmlspecialchars($viewFeedback['message'])) ?></div>
                </div>

                <div class="detail-sidebar">
                    <div class="detail-card">
                        <h3>Details</h3>
                        <div class="info-row"><span class="label">Received</span><span><?= date('M j, Y g:i A', strtotime($viewFeedback['created_at'])) ?></span></div>
                        <div class="info-row"><span class="label">Status</span><span class="status-badge status-<?= $viewFeedback['status'] ?>"><?= ucfirst($viewFeedback['status']) ?></span></div>
                        <?php if ($viewFeedback['user_id']): ?>
                        <div class="info-row"><span class="label">Customer</span><span><a href="customers.php?id=<?= $viewFeedback['user_id'] ?>">View Profile</a></span></div>
                        <?php endif; ?>
                    </div>

                    <div class="detail-card">
                        <h3>Update Status</h3>
                        <form method="POST">
                            <input type="hidden" name="feedback_id" value="<?= $viewFeedback['id'] ?>">
                            <div class="form-group">
                                <select name="status" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                                    <option value="new" <?= $viewFeedback['status'] == 'new' ? 'selected' : '' ?>>New</option>
                                    <option value="read" <?= $viewFeedback['status'] == 'read' ? 'selected' : '' ?>>Read</option>
                                    <option value="replied" <?= $viewFeedback['status'] == 'replied' ? 'selected' : '' ?>>Replied</option>
                                    <option value="resolved" <?= $viewFeedback['status'] == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-size: 14px;">Admin Notes</label>
                                <textarea name="admin_notes" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" placeholder="Internal notes..."><?= htmlspecialchars($viewFeedback['admin_notes'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" name="update_status" class="btn-primary" style="width: 100%;">Update</button>
                        </form>
                    </div>

                    <div class="detail-card">
                        <h3>Quick Reply</h3>
                        <a href="mailto:<?= htmlspecialchars($viewFeedback['email']) ?>?subject=Re: <?= urlencode($viewFeedback['subject']) ?>" class="btn-primary" style="display: block; text-align: center; text-decoration: none;">
                            📧 Reply via Email
                        </a>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- Feedback List -->
            <div class="feedback-stats">
                <a href="feedback.php" class="mini-stat <?= !$statusFilter ? 'active' : '' ?>">
                    <h4><?= $counts['total'] ?></h4>
                    <p>Total</p>
                </a>
                <a href="feedback.php?status=new" class="mini-stat new <?= $statusFilter == 'new' ? 'active' : '' ?>">
                    <h4><?= $counts['new'] ?></h4>
                    <p>New</p>
                </a>
                <a href="feedback.php?status=read" class="mini-stat <?= $statusFilter == 'read' ? 'active' : '' ?>">
                    <h4><?= $counts['read'] ?></h4>
                    <p>Read</p>
                </a>
                <a href="feedback.php?status=replied" class="mini-stat <?= $statusFilter == 'replied' ? 'active' : '' ?>">
                    <h4><?= $counts['replied'] ?></h4>
                    <p>Replied</p>
                </a>
                <a href="feedback.php?status=resolved" class="mini-stat <?= $statusFilter == 'resolved' ? 'active' : '' ?>">
                    <h4><?= $counts['resolved'] ?></h4>
                    <p>Resolved</p>
                </a>
            </div>

            <?php if (empty($feedbacks)): ?>
            <div class="empty-state">
                <h3>No feedback yet</h3>
                <p>When customers send messages, they'll appear here.</p>
            </div>
            <?php else: ?>
            <?php foreach ($feedbacks as $fb): ?>
            <div class="feedback-item <?= $fb['status'] === 'new' ? 'unread' : '' ?>">
                <div>
                    <div class="feedback-meta">
                        <strong><?= htmlspecialchars($fb['name'] ?: 'Anonymous') ?></strong>
                        <span><?= htmlspecialchars($fb['email']) ?></span>
                        <span><?= date('M j, Y g:i A', strtotime($fb['created_at'])) ?></span>
                        <span class="status-badge status-<?= $fb['status'] ?>"><?= ucfirst($fb['status']) ?></span>
                    </div>
                    <div class="feedback-subject"><?= htmlspecialchars($fb['subject'] ?: 'No Subject') ?></div>
                    <div class="feedback-preview"><?= htmlspecialchars(substr($fb['message'], 0, 150)) ?><?= strlen($fb['message']) > 150 ? '...' : '' ?></div>
                </div>
                <div class="feedback-actions">
                    <a href="feedback.php?id=<?= $fb['id'] ?>" class="btn-view">View</a>
                    <a href="feedback.php?delete=<?= $fb['id'] ?>" class="btn-delete" onclick="return confirm('Delete this feedback?')">Delete</a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>