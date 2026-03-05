<?php
// admin/get-ticket.php - AJAX endpoint to view ticket details
//
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    exit('Unauthorized');
}

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT t.*, u.fullname, u.email 
    FROM support_tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    exit('Ticket not found');
}
?>
<div class="ticket-detail">
    <label>Ticket #<?php echo $ticket['id']; ?></label>
    <p><strong>From:</strong> <?php echo htmlspecialchars($ticket['fullname']); ?> (<?php echo $ticket['email']; ?>)</p>
    <p><strong>Subject:</strong> <?php echo htmlspecialchars($ticket['subject']); ?></p>
    <p><strong>Category:</strong> <?php echo ucfirst($ticket['category']); ?></p>
    <p><strong>Priority:</strong> <span class="priority-badge priority-<?php echo $ticket['priority']; ?>"><?php echo ucfirst($ticket['priority']); ?></span></p>
    <p><strong>Status:</strong> <span class="status-badge status-<?php echo str_replace('_', '-', $ticket['status']); ?>"><?php echo ucfirst($ticket['status']); ?></span></p>
    <p><strong>Created:</strong> <?php echo date('F j, Y g:i a', strtotime($ticket['created_at'])); ?></p>
</div>

<div class="ticket-detail">
    <label>Message</label>
    <p><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></p>
</div>

<?php if (!empty($ticket['admin_response'])): ?>
<div class="ticket-detail" style="background: #E8F5E9;">
    <label>Admin Response</label>
    <p><?php echo nl2br(htmlspecialchars($ticket['admin_response'])); ?></p>
    <small>Responded on <?php echo date('F j, Y', strtotime($ticket['updated_at'])); ?></small>
</div>
<?php endif; ?>