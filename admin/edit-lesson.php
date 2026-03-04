<?php
// admin/edit-lesson.php - Edit Existing Lessons
// This page allows admins to edit existing lessons, including title, description, content, and associated media.
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser($pdo);
$success = '';
$error = '';

// Get lesson ID from URL
$lesson_id = $_GET['id'] ?? 0;

// Fetch lesson details
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    header('Location: lessons.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lesson'])) {
    $class = sanitize($_POST['class']);
    $subject = sanitize($_POST['subject']);
    $topic = sanitize($_POST['topic']);
    $description = sanitize($_POST['description']);
    $week = $_POST['week'];
    $duration = sanitize($_POST['duration']);
    $is_free = isset($_POST['is_free']) ? 1 : 0;
    $status = $_POST['status'];
    
    // Handle video upload
    $video_path = $lesson['video_path'];
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/videos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Delete old video
        if (!empty($lesson['video_path']) && file_exists($upload_dir . $lesson['video_path'])) {
            unlink($upload_dir . $lesson['video_path']);
        }
        
        $file_ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $topic) . '.' . $file_ext;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $target_path)) {
            $video_path = $filename;
        }
    }
    
    // Handle PDF upload
    $pdf_path = $lesson['pdf_path'];
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/pdfs/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Delete old PDF
        if (!empty($lesson['pdf_path']) && file_exists($upload_dir . $lesson['pdf_path'])) {
            unlink($upload_dir . $lesson['pdf_path']);
        }
        
        $file_ext = pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $topic) . '.' . $file_ext;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_path)) {
            $pdf_path = $filename;
        }
    }
    
    // Handle thumbnail upload
    $thumbnail = $lesson['thumbnail'];
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/thumbnails/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Delete old thumbnail
        if (!empty($lesson['thumbnail']) && file_exists($upload_dir . $lesson['thumbnail'])) {
            unlink($upload_dir . $lesson['thumbnail']);
        }
        
        $file_ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $topic) . '.' . $file_ext;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $target_path)) {
            $thumbnail = $filename;
        }
    }
    
    // Update database
    $stmt = $pdo->prepare("
        UPDATE lessons 
        SET class = ?, subject = ?, topic = ?, description = ?, week = ?, 
            duration = ?, video_path = ?, pdf_path = ?, thumbnail = ?, 
            is_free = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    if ($stmt->execute([$class, $subject, $topic, $description, $week, $duration, 
                        $video_path, $pdf_path, $thumbnail, $is_free, $status, $lesson_id])) {
        $success = 'Lesson updated successfully';
        logActivity($pdo, $user['id'], 'admin_edit_lesson', "Edited lesson ID: $lesson_id");
        
        // Refresh lesson data
        $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
        $stmt->execute([$lesson_id]);
        $lesson = $stmt->fetch();
    } else {
        $error = 'Failed to update lesson';
    }
}

// Get all classes and subjects for dropdowns
$classes = ['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'];
$lower_subjects = ['Literacy 1A', 'Literacy 1B', 'Mathematics', 'Reading', 'Writing', 'English Language', 'Religious Education'];
$upper_subjects = ['Kiswahili', 'English Language', 'Religious Education', 'Mathematics', 'Integrated Science', 'Social Studies'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lesson - RAYS OF GRACE</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f4f4f9;
        }

        /* Top Navigation */
        .admin-topnav {
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 4px 15px rgba(75,28,60,0.3);
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-area img {
            height: 45px;
            width: auto;
            background: white;
            border-radius: 8px;
            padding: 5px;
        }

        .logo-area span {
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-badge {
            background: #FFB800;
            color: #4B1C3C;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
        }

        .nav-btn {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background: #FFB800;
            color: #4B1C3C;
        }

        /* Main Container */
        .edit-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            color: #4B1C3C;
            font-size: 2rem;
        }

        .page-header h1 i {
            color: #FFB800;
            margin-right: 10px;
        }

        .btn-back {
            background: #666;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }

        .btn-back:hover {
            background: #4B1C3C;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #E8F5E9;
            border-left: 4px solid #4CAF50;
            color: #2E7D32;
        }

        .alert-error {
            background: #FFEBEE;
            border-left: 4px solid #f44336;
            color: #C62828;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-section h2 {
            color: #4B1C3C;
            font-size: 1.2rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-section h2 i {
            color: #FFB800;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4B1C3C;
            font-weight: 500;
        }

        .form-group label i {
            color: #FFB800;
            margin-right: 5px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFB800;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* Current Files */
        .current-files {
            background: #f8f4f8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .current-files h4 {
            color: #4B1C3C;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .file-list {
            list-style: none;
        }

        .file-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .file-list li:last-child {
            border-bottom: none;
        }

        .file-list i {
            color: #FFB800;
        }

        .file-list a {
            color: #4B1C3C;
            text-decoration: none;
        }

        .file-list a:hover {
            color: #FFB800;
        }

        /* File Upload */
        .file-upload {
            position: relative;
            border: 2px dashed #e0e0e0;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            background: #f8f4f8;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }

        .file-upload:hover {
            border-color: #FFB800;
        }

        .file-upload input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload i {
            font-size: 2rem;
            color: #FFB800;
            margin-bottom: 10px;
        }

        .file-upload p {
            color: #666;
        }

        .file-upload small {
            color: #999;
        }

        /* Checkbox */
        .checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .checkbox input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-primary {
            background: #4B1C3C;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #2F1224;
        }

        .btn-secondary {
            background: #666;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .btn-secondary:hover {
            background: #4B1C3C;
        }

        /* Preview Images */
        .image-preview {
            margin-top: 10px;
            max-width: 200px;
            border-radius: 5px;
            overflow: hidden;
            border: 2px solid #e0e0e0;
        }

        .image-preview img {
            width: 100%;
            height: auto;
            display: block;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="admin-topnav">
        <div class="logo-area">
            <img src="../images/logo.png" alt="RAYS OF GRACE">
            <span>Edit Lesson</span>
        </div>
        
        <div class="admin-profile">
            <span class="admin-badge">
                <i class="fas fa-shield-alt"></i> <?php echo explode(' ', $user['fullname'])[0]; ?>
            </span>
            <a href="../logout.php" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="edit-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> Edit Lesson</h1>
            <a href="lessons.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Lessons
            </a>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="form-card">
            <form method="POST" enctype="multipart/form-data">
                <!-- Basic Information -->
                <div class="form-section">
                    <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-graduation-cap"></i> Class</label>
                            <select name="class" class="form-control" required>
                                <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c; ?>" <?php echo $lesson['class'] == $c ? 'selected' : ''; ?>>
                                    <?php echo $c; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-book"></i> Subject</label>
                            <select name="subject" id="subject" class="form-control" required>
                                <option value="">Select Subject</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-heading"></i> Lesson Topic</label>
                        <input type="text" name="topic" class="form-control" 
                               value="<?php echo htmlspecialchars($lesson['topic']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($lesson['description']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar-week"></i> Week Number</label>
                            <input type="number" name="week" class="form-control" min="1" max="13" 
                                   value="<?php echo $lesson['week']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Duration</label>
                            <input type="text" name="duration" class="form-control" 
                                   value="<?php echo $lesson['duration']; ?>" placeholder="e.g., 40 min">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox">
                            <input type="checkbox" name="is_free" <?php echo $lesson['is_free'] ? 'checked' : ''; ?>>
                            <span>Make this lesson free (no subscription required)</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-eye"></i> Status</label>
                        <select name="status" class="form-control">
                            <option value="published" <?php echo $lesson['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?php echo $lesson['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                </div>

                <!-- Video File -->
                <div class="form-section">
                    <h2><i class="fas fa-video"></i> Video Lesson</h2>
                    
                    <?php if (!empty($lesson['video_path'])): ?>
                    <div class="current-files">
                        <h4>Current Video:</h4>
                        <ul class="file-list">
                            <li>
                                <i class="fas fa-video"></i>
                                <a href="../uploads/videos/<?php echo $lesson['video_path']; ?>" target="_blank">
                                    <?php echo $lesson['video_path']; ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="file-upload">
                        <input type="file" name="video_file" accept="video/mp4,video/avi,video/mov">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload new video</p>
                        <small>MP4, AVI, MOV (Max: 500MB)</small>
                    </div>
                </div>

                <!-- PDF File -->
                <div class="form-section">
                    <h2><i class="fas fa-file-pdf"></i> Workbook (PDF)</h2>
                    
                    <?php if (!empty($lesson['pdf_path'])): ?>
                    <div class="current-files">
                        <h4>Current PDF:</h4>
                        <ul class="file-list">
                            <li>
                                <i class="fas fa-file-pdf"></i>
                                <a href="../uploads/pdfs/<?php echo $lesson['pdf_path']; ?>" target="_blank">
                                    <?php echo $lesson['pdf_path']; ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="file-upload">
                        <input type="file" name="pdf_file" accept=".pdf">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload new PDF</p>
                        <small>PDF only (Max: 50MB)</small>
                    </div>
                </div>

                <!-- Thumbnail Image -->
                <div class="form-section">
                    <h2><i class="fas fa-image"></i> Thumbnail Image</h2>
                    
                    <?php if (!empty($lesson['thumbnail'])): ?>
                    <div class="current-files">
                        <h4>Current Thumbnail:</h4>
                        <div class="image-preview">
                            <img src="../uploads/thumbnails/<?php echo $lesson['thumbnail']; ?>" alt="Thumbnail">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="file-upload">
                        <input type="file" name="thumbnail" accept="image/*">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload new thumbnail</p>
                        <small>JPG, PNG (Recommended: 1280x720)</small>
                    </div>
                    <div id="imagePreview" class="image-preview" style="display: none;"></div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" name="update_lesson" class="btn-primary">
                        <i class="fas fa-save"></i> Update Lesson
                    </button>
                    <a href="lessons.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Subject data
        const subjects = {
            'P1': ['Literacy 1A', 'Literacy 1B', 'Mathematics', 'Reading', 'Writing', 'English Language', 'Religious Education'],
            'P2': ['Literacy 1A', 'Literacy 1B', 'Mathematics', 'Reading', 'Writing', 'English Language', 'Religious Education'],
            'P3': ['Literacy 1A', 'Literacy 1B', 'Mathematics', 'Reading', 'Writing', 'English Language', 'Religious Education'],
            'P4': ['Kiswahili', 'English Language', 'Religious Education', 'Mathematics', 'Integrated Science', 'Social Studies'],
            'P5': ['Kiswahili', 'English Language', 'Religious Education', 'Mathematics', 'Integrated Science', 'Social Studies'],
            'P6': ['Kiswahili', 'English Language', 'Religious Education', 'Mathematics', 'Integrated Science', 'Social Studies'],
            'P7': ['Kiswahili', 'English Language', 'Religious Education', 'Mathematics', 'Integrated Science', 'Social Studies']
        };

        // Get elements
        const classSelect = document.querySelector('select[name="class"]');
        const subjectSelect = document.getElementById('subject');
        const currentSubject = '<?php echo $lesson['subject']; ?>';

        // Function to update subjects
        function updateSubjects() {
            const selectedClass = classSelect.value;
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            
            if (selectedClass && subjects[selectedClass]) {
                subjects[selectedClass].forEach(subject => {
                    const option = document.createElement('option');
                    option.value = subject;
                    option.textContent = subject;
                    if (subject === currentSubject) {
                        option.selected = true;
                    }
                    subjectSelect.appendChild(option);
                });
            }
        }

        // Initial update
        updateSubjects();

        // Add event listener
        classSelect.addEventListener('change', updateSubjects);

        // Image preview
        document.querySelector('input[name="thumbnail"]').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(e.target.files[0]);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>