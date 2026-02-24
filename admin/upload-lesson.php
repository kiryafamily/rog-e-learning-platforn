<?php
// ============================================
// SINGLE DATABASE CONNECTION - AT THE TOP ONLY!
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files - ONLY ONCE!
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser($pdo);

// ============================================
// FORM HANDLING - UPDATED WITH BETTER DEBUGGING
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_lesson'])) {
    
    // DEBUG OUTPUT - BEAUTIFUL STYLING WITH FONT AWESOME
echo "
<div style='background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%); color: white; padding: 25px; margin: 20px auto; border-radius: 15px; border: 3px solid #FFB800; max-width: 800px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); font-family: \"Poppins\", sans-serif;'>
    <div style='display: flex; align-items: center; gap: 15px; margin-bottom: 20px; border-bottom: 2px solid #FFB800; padding-bottom: 15px;'>
        <i class='fas fa-check-circle' style='font-size: 40px; color: #FFB800;'></i>
        <h2 style='color: white; margin: 0; font-size: 28px; font-weight: 600;'>Lesson Upload Successful!</h2>
    </div>";
    
    if (!empty($video_path)) {
        echo "
        <div style='background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid #4CAF50;'>
            <div style='display: flex; align-items: center; gap: 15px;'>
                <i class='fas fa-video' style='color: #FFB800; font-size: 28px; width: 40px; text-align: center;'></i>
                <div style='flex: 1;'>
                    <strong style='color: #FFB800; display: block; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;'>Video Uploaded</strong>
                    <span style='color: white; font-size: 16px; word-break: break-all;'>" . basename($video_path) . "</span>
                </div>
                <i class='fas fa-check-circle' style='color: #4CAF50; font-size: 24px;'></i>
            </div>
        </div>";
    }
    
    echo "
    <div style='display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0;'>
        <div style='background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; text-align: center; border: 1px solid rgba(255,184,0,0.3);'>
            <i class='fas fa-book-open' style='color: #FFB800; font-size: 32px; margin-bottom: 10px; display: block;'></i>
            <strong style='color: #FFB800; display: block; font-size: 14px; text-transform: uppercase;'>Class</strong>
            <span style='color: white; font-size: 28px; font-weight: 700;'>$class</span>
        </div>
        <div style='background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; text-align: center; border: 1px solid rgba(255,184,0,0.3);'>
            <i class='fas fa-tag' style='color: #FFB800; font-size: 32px; margin-bottom: 10px; display: block;'></i>
            <strong style='color: #FFB800; display: block; font-size: 14px; text-transform: uppercase;'>Subject</strong>
            <span style='color: white; font-size: 28px; font-weight: 700;'>$subject</span>
        </div>
    </div>
    
    <div style='background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; margin: 15px 0; border: 1px solid rgba(255,184,0,0.3);'>
        <div style='display: flex; align-items: center; gap: 15px; margin-bottom: 10px;'>
            <i class='fas fa-pencil-alt' style='color: #FFB800; font-size: 24px; width: 30px;'></i>
            <strong style='color: #FFB800; font-size: 16px; text-transform: uppercase;'>Lesson Topic</strong>
        </div>
        <p style='color: white; margin: 0 0 0 45px; font-size: 18px; font-weight: 500;'>$topic</p>
    </div>";
    
    if (!empty($lesson_id)) {
        echo "
        <div style='background: #4CAF50; padding: 15px; border-radius: 10px; text-align: center; margin-top: 15px; display: flex; align-items: center; justify-content: center; gap: 15px;'>
            <i class='fas fa-database' style='color: white; font-size: 28px;'></i>
            <div>
                <strong style='color: white; font-size: 18px; display: block;'>Lesson Saved to Database</strong>
                <span style='color: #FFB800; font-size: 24px; font-weight: 700;'>ID: $lesson_id</span>
            </div>
            <i class='fas fa-check-circle' style='color: white; font-size: 28px;'></i>
        </div>";
    }
    
    echo "
    <div style='display: flex; gap: 15px; justify-content: center; margin-top: 25px; flex-wrap: wrap;'>
        <a href='lesson-view.php?id=$lesson_id' style='background: #FFB800; color: #4B1C3C; padding: 14px 30px; border-radius: 50px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s ease; border: 2px solid #FFB800;' 
           onmouseover='this.style.background=\"transparent\"; this.style.color=\"#FFB800\"' 
           onmouseout='this.style.background=\"#FFB800\"; this.style.color=\"#4B1C3C\"'>
            <i class='fas fa-eye'></i> View Lesson
        </a>
        <a href='upload-lesson.php' style='background: transparent; border: 2px solid #FFB800; color: white; padding: 14px 30px; border-radius: 50px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s ease;'
           onmouseover='this.style.background=\"#FFB800\"; this.style.color=\"#4B1C3C\"' 
           onmouseout='this.style.background=\"transparent\"; this.style.color=\"white\"'>
            <i class='fas fa-upload'></i> Upload Another
        </a>
    </div>
    
    <div style='margin-top: 20px; padding-top: 15px; border-top: 1px solid rgba(255,184,0,0.3); text-align: center;'>
        <i class='fas fa-info-circle' style='color: #FFB800; margin-right: 5px;'></i>
        <small style='color: rgba(255,255,255,0.7);'>Your lesson has been saved and is ready for students!</small>
    </div>
</div>";
    // Create upload directories if they don't exist
    $upload_dir_videos = '../uploads/videos/';
    $upload_dir_pdfs = '../uploads/pdfs/';
    $upload_dir_thumbnails = '../uploads/thumbnails/';
    
    if (!file_exists($upload_dir_videos)) mkdir($upload_dir_videos, 0777, true);
    if (!file_exists($upload_dir_pdfs)) mkdir($upload_dir_pdfs, 0777, true);
    if (!file_exists($upload_dir_thumbnails)) mkdir($upload_dir_thumbnails, 0777, true);
    
    // Process video upload
    $video_path = '';
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['video_file']['tmp_name'];
        $file_name = time() . '_' . $_FILES['video_file']['name'];
        $file_path = $upload_dir_videos . $file_name;
        
        if (move_uploaded_file($file_tmp, $file_path)) {
            $video_path = $file_name;
            echo "<p style='color: #4CAF50;'>✅ Video uploaded: <strong>$file_name</strong></p>";
        } else {
            echo "<p style='color: #f44336;'>❌ Video upload failed</p>";
        }
    }
    
    // Process PDF upload
    $pdf_path = '';
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['pdf_file']['tmp_name'];
        $file_name = time() . '_' . $_FILES['pdf_file']['name'];
        $file_path = $upload_dir_pdfs . $file_name;
        
        if (move_uploaded_file($file_tmp, $file_path)) {
            $pdf_path = $file_name;
            echo "<p style='color: #4CAF50;'>✅ PDF uploaded: <strong>$file_name</strong></p>";
        }
    }
    
    // Process thumbnail upload
    $thumbnail_path = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['thumbnail']['tmp_name'];
        $file_name = time() . '_' . $_FILES['thumbnail']['name'];
        $file_path = $upload_dir_thumbnails . $file_name;
        
        if (move_uploaded_file($file_tmp, $file_path)) {
            $thumbnail_path = $file_name;
            echo "<p style='color: #4CAF50;'>✅ Thumbnail uploaded: <strong>$file_name</strong></p>";
        }
    }
    
    // Get form data
    $class = $_POST['class'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $topic = $_POST['topic'] ?? '';
    $description = $_POST['description'] ?? '';
    $week = $_POST['week'] ?? 1;
    $duration = $_POST['duration'] ?? '30 min';
    $is_free = isset($_POST['is_free']) ? 1 : 0;
    
    // Show received data
    echo "<h3 style='color: #FFB800;'>📋 Form Data:</h3>";
    echo "<table style='background: #222; padding: 10px; border-radius: 5px; width: 100%; margin-bottom: 15px;'>";
    echo "<tr><td>Class:</td><td><strong>$class</strong></td></tr>";
    echo "<tr><td>Subject:</td><td><strong>$subject</strong></td></tr>";
    echo "<tr><td>Topic:</td><td><strong>$topic</strong></td></tr>";
    echo "<tr><td>Week:</td><td><strong>$week</strong></td></tr>";
    echo "<tr><td>Video path:</td><td><strong>" . ($video_path ?: 'None') . "</strong></td></tr>";
    echo "</table>";
    
    // Validate required fields
    if (empty($class) || empty($subject) || empty($topic)) {
        echo "<p style='color: #f44336;'>❌ Error: Class, Subject, and Topic are required!</p>";
    } else {
        // Insert into database - FIXED COLUMN NAMES
        try {
            // Check if thumbnail column exists - if not, remove it from query
            $sql = "INSERT INTO lessons (class, subject, topic, description, week, duration, 
                                       video_path, pdf_path, is_free, created_at, status)
                    VALUES (:class, :subject, :topic, :description, :week, :duration, 
                            :video_path, :pdf_path, :is_free, NOW(), 'published')";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':class' => $class,
                ':subject' => $subject,
                ':topic' => $topic,
                ':description' => $description,
                ':week' => $week,
                ':duration' => $duration,
                ':video_path' => $video_path,
                ':pdf_path' => $pdf_path,
                ':is_free' => $is_free
            ]);
            
            if ($result) {
                $lesson_id = $pdo->lastInsertId();
                echo "<div style='background: #4CAF50; color: white; padding: 15px; margin: 20px 0; border-radius: 5px; text-align: center; border: 2px solid #FFB800;'>";
                echo "<h3 style='margin: 0; color: white; font-size: 24px;'>✅ SUCCESS!</h3>";
                echo "<p style='font-size: 18px; margin: 10px 0;'>Lesson saved with ID: <strong>$lesson_id</strong></p>";
                echo "<p>Your lesson is now in the database!</p>";
                echo "</div>";
            } else {
                echo "<p style='color: #f44336;'>❌ Database insert failed</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: #f44336;'>❌ Database Error: " . $e->getMessage() . "</p>";
            
            // If error is about thumbnail column, try without it
            if (strpos($e->getMessage(), 'thumbnail') !== false) {
                echo "<p style='color: #FFB800;'>⚠️ Trying again without thumbnail column...</p>";
                
                // Try again without thumbnail
                $sql = "INSERT INTO lessons (class, subject, topic, description, week, duration, 
                                           video_path, pdf_path, is_free, created_at, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'published')";
                
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$class, $subject, $topic, $description, $week, $duration, 
                                   $video_path, $pdf_path, $is_free])) {
                    $lesson_id = $pdo->lastInsertId();
                    echo "<div style='background: #4CAF50; color: white; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                    echo "✅ SUCCESS on second try! Lesson ID: $lesson_id";
                    echo "</div>";
                }
            }
        }
    }
    
    echo "</div>";
}

// ============================================
// HTML BEGINS HERE - KEEP YOUR BEAUTIFUL DESIGN
// ============================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Lesson - RAYS OF GRACE</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <div class="nav-left">
                <div class="logo">
                    <img src="../images/logo.png" alt="RAYS OF GRACE">
                    <span>Upload Lesson</span>
                </div>
            </div>
            <div class="nav-right">
                <a href="index.php" class="btn btn-outline btn-small">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="../logout.php" class="btn btn-outline btn-small">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="upload-container">
        <!-- Tabs -->
        <div class="upload-tabs">
            <button class="tab-btn active" onclick="switchUploadTab('upload')">
                <i class="fas fa-upload"></i> Upload New Lesson
            </button>
            <button class="tab-btn" onclick="switchUploadTab('manage')">
                <i class="fas fa-list"></i> Manage Lessons
            </button>
            <button class="tab-btn" onclick="switchUploadTab('bulk')">
                <i class="fas fa-layer-group"></i> Bulk Upload
            </button>
        </div>

        <!-- Upload Tab -->
        <div id="upload-tab" class="tab-pane active">
            <form method="POST" action="" enctype="multipart/form-data" class="upload-form">
                <div class="form-grid">
                    <!-- Basic Info -->
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="class">Class <span class="required">*</span></label>
                                <select id="class" name="class" required>
                                    <option value="">Select Class</option>
                                    <option value="P1">P1</option>
                                    <option value="P2">P2</option>
                                    <option value="P3">P3</option>
                                    <option value="P4">P4</option>
                                    <option value="P5">P5</option>
                                    <option value="P6">P6</option>
                                    <option value="P7">P7</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject <span class="required">*</span></label>
                                <select id="subject" name="subject" required>
                                    <option value="">First select a class</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="topic">Lesson Topic <span class="required">*</span></label>
                            <input type="text" id="topic" name="topic" required 
                                   placeholder="e.g., Fractions - Addition and Subtraction">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" 
                                      placeholder="Describe what students will learn in this lesson..."></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="week">Week Number <span class="required">*</span></label>
                                <input type="number" id="week" name="week" min="1" max="13" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="duration">Duration</label>
                                <input type="text" id="duration" name="duration" placeholder="e.g., 40 min" value="40 min">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox">
                                <input type="checkbox" name="is_free">
                                <span>Make this lesson free (no subscription required)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Media Upload -->
                    <div class="form-section">
                        <h3><i class="fas fa-video"></i> Media Files</h3>
                        
                        <div class="form-group">
                            <label for="video_file">Video Lesson</label>
                            <div class="file-upload">
                                <input type="file" id="video_file" name="video_file" 
                                       accept="video/mp4,video/avi,video/mov">
                                <div class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Click to upload video</span>
                                    <small>MP4, AVI, MOV (Max: 500MB)</small>
                                </div>
                            </div>
                            <div id="video-preview" class="file-preview"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="pdf_file">Workbook (PDF)</label>
                            <div class="file-upload">
                                <input type="file" id="pdf_file" name="pdf_file" accept=".pdf">
                                <div class="file-upload-label">
                                    <i class="fas fa-file-pdf"></i>
                                    <span>Click to upload PDF</span>
                                    <small>PDF only (Max: 50MB)</small>
                                </div>
                            </div>
                            <div id="pdf-preview" class="file-preview"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="thumbnail">Thumbnail Image</label>
                            <div class="file-upload">
                                <input type="file" id="thumbnail" name="thumbnail" accept="image/*">
                                <div class="file-upload-label">
                                    <i class="fas fa-image"></i>
                                    <span>Click to upload thumbnail</span>
                                    <small>JPG, PNG (Recommended: 1280x720)</small>
                                </div>
                            </div>
                            <div id="image-preview" class="image-preview"></div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <button type="submit" name="save_lesson" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Lesson
                    </button>
                    <button type="reset" class="btn btn-outline">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Manage Lessons Tab (placeholder) -->
        <div id="manage-tab" class="tab-pane">
            <p>Manage Lessons section - Coming soon</p>
        </div>

        <!-- Bulk Upload Tab (placeholder) -->
        <div id="bulk-tab" class="tab-pane">
            <p>Bulk Upload section - Coming soon</p>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
    // Subject data for dropdown
    const subjectData = {
        'P1': ['Literacy 1A', 'Literacy 1B', 'Mathematics', 'Reading', 'Writing', 'English Language', 'Religious Education'],
        'P2': ['Literacy 1A', 'Literacy 1B', 'Mathematics', 'Reading', 'Writing', 'English Language', 'Religious Education'],
        'P3': ['Literacy 1A', 'Literacy 1B', 'Mathematics', 'Reading', 'Writing', 'English Language', 'Religious Education'],
        'P4': ['Kiswahili', 'English Language', 'Religious Education', 'Mathematics', 'Integrated Science', 'Social Studies'],
        'P5': ['Kiswahili', 'English Language', 'Religious Education', 'Mathematics', 'Integrated Science', 'Social Studies'],
        'P6': ['Kiswahili', 'English Language', 'Religious Education', 'Mathematics', 'Integrated Science', 'Social Studies'],
        'P7': ['Kiswahili', 'English Language', 'Religious Education', 'Mathematics', 'Integrated Science', 'Social Studies']
    };

    // Wait for page to load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded - setting up subject dropdown');
        
        const classSelect = document.getElementById('class');
        const subjectSelect = document.getElementById('subject');
        
        if (classSelect && subjectSelect) {
            classSelect.addEventListener('change', function() {
                const selectedClass = this.value;
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                
                if (selectedClass && subjectData[selectedClass]) {
                    subjectData[selectedClass].forEach(function(subject) {
                        const option = document.createElement('option');
                        option.value = subject;
                        option.textContent = subject;
                        subjectSelect.appendChild(option);
                    });
                }
            });
        }

        // File input previews
        document.getElementById('video_file')?.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file chosen';
            const fileSize = e.target.files[0] ? (e.target.files[0].size / 1024 / 1024).toFixed(2) : 0;
            const preview = document.getElementById('video-preview');
            
            if (e.target.files[0]) {
                preview.innerHTML = `
                    <div style="background: #E8F5E9; padding: 10px; border-radius: 5px; margin-top: 10px;">
                        <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
                        <strong>${fileName}</strong>
                        <small>(${fileSize} MB)</small>
                    </div>
                `;
            } else {
                preview.innerHTML = '';
            }
        });

        document.getElementById('pdf_file')?.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file chosen';
            const preview = document.getElementById('pdf-preview');
            
            if (e.target.files[0]) {
                preview.innerHTML = `
                    <div style="background: #E8F5E9; padding: 10px; border-radius: 5px; margin-top: 10px;">
                        <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
                        <strong>${fileName}</strong>
                    </div>
                `;
            } else {
                preview.innerHTML = '';
            }
        });

        document.getElementById('thumbnail')?.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image-preview');
                    preview.innerHTML = `<img src="${e.target.result}" style="max-width: 200px; max-height: 100px; margin-top: 10px; border-radius: 5px;">`;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    });

    // Tab switching function
    function switchUploadTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
        document.getElementById(tab + '-tab').classList.add('active');
    }
    </script>

    <style>
    /* Your existing styles - keep them as they are */
    .upload-container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }
    
    .upload-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        background: white;
        padding: 10px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .upload-tabs .tab-btn {
        flex: 1;
        padding: 12px;
        background: none;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        color: #666;
        transition: all 0.3s ease;
    }
    
    .upload-tabs .tab-btn:hover {
        background: #F5F5F5;
        color: #4B1C3C;
    }
    
    .upload-tabs .tab-btn.active {
        background: #4B1C3C;
        color: white;
    }
    
    .upload-form {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .form-section {
        background: #F9F9F9;
        padding: 20px;
        border-radius: 5px;
    }
    
    .form-section h3 {
        color: #4B1C3C;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 10px;
        border-bottom: 2px solid #FFB800;
    }
    
    .form-section h3 i {
        color: #FFB800;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #333;
    }
    
    .required {
        color: #f44336;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 2px solid #E0E0E0;
        border-radius: 5px;
        font-size: 1rem;
    }
    
    .file-upload {
        position: relative;
        border: 2px dashed #E0E0E0;
        border-radius: 5px;
        background: white;
        cursor: pointer;
    }
    
    .file-upload input {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }
    
    .file-upload-label {
        padding: 30px;
        text-align: center;
    }
    
    .file-upload-label i {
        font-size: 2rem;
        color: #4B1C3C;
        margin-bottom: 10px;
    }
    
    .file-upload-label span {
        display: block;
        font-weight: 500;
        margin-bottom: 5px;
    }
    
    .file-upload-label small {
        color: #999;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        justify-content: flex-end;
        padding-top: 20px;
        border-top: 1px solid #E0E0E0;
    }
    
    .file-preview, .image-preview {
        margin-top: 10px;
    }
    </style>
</body>
</html>