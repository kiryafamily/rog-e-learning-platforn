<?php
// admin/reply-ticket.php - AJAX endpoint for reply form
// This file is used to load the reply form for a specific support ticket when an admin clicks "Reply" in the ticket details modal. It retrieves the ticket information and displays a form for the admin to enter their response and update the ticket status.
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
<form method="POST" action="tickets.php" class="response-form">
    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
    
    <div class="ticket-detail">
        <p><strong>From:</strong> <?php echo htmlspecialchars($ticket['fullname']); ?></p>
        <p><strong>Subject:</strong> <?php echo htmlspecialchars($ticket['subject']); ?></p>
    </div>
    
    <div class="ticket-detail">
        <label>Original Message</label>
        <p><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></p>
    </div>
    
    <div class="form-group">
        <label for="response">Your Response *</label>
        <textarea id="response" name="response" rows="5" required placeholder="Type your response here..."></textarea>
    </div>
    
    <div class="form-group">
        <label for="status">Update Status</label>
        <select id="status" name="status">
            <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
            <option value="in-progress" <?php echo $ticket['status'] === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
            <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
        </select>
    </div>
    
    <button type="submit" name="respond_ticket" class="btn-primary">
        <i class="fas fa-paper-plane"></i> Send Response
    </button>
</form>