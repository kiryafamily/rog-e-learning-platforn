<?php
// send-message.php - Complete working version
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Start session for messages
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            // Insert into database
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([$name, $email, $phone, $subject, $message]);
            
            if ($result) {
                // Success! Set session message
                $_SESSION['contact_success'] = true;
                $_SESSION['contact_message'] = 'Thank you for contacting us! We will get back to you soon.';
                
                // Optional: Send email notification to admin
                $admin_email = "admin@raysofgrace.ac.ug";
                $email_subject = "New Contact Form Message: $subject";
                $email_body = "
                    <h2>New Contact Form Submission</h2>
                    <p><strong>Name:</strong> $name</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Phone:</strong> $phone</p>
                    <p><strong>Subject:</strong> $subject</p>
                    <p><strong>Message:</strong></p>
                    <p>" . nl2br($message) . "</p>
                ";
                
                // You can uncomment this when email is configured
                // sendEmail($admin_email, $email_subject, $email_body);
                
                // Redirect back to contact page
                header('Location: contact.php');
                exit;
            } else {
                $_SESSION['contact_error'] = 'Failed to send message. Please try again.';
            }
        } catch (PDOException $e) {
            $_SESSION['contact_error'] = 'Database error. Please try again later.';
            // Log error: error_log($e->getMessage());
        }
    } else {
        // Store errors in session
        //  
        $_SESSION['contact_errors'] = $errors;
        $_SESSION['contact_form_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message
        ];
    }
    
    // If we get here, something went wrong - redirect back
    header('Location: contact.php');
    exit;
} else {
    // Not a POST request - redirect to contact page
    header('Location: contact.php');
    exit;
}
?>