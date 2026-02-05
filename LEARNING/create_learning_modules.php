<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learning_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize all variables with default values
$module_title = '';
$module_topic = '';
$module_department = '';
$module_role = '';
$module_content = '';
$module_status = 'pending';
$edit_mode = false;
$module_id = null;

// IMPROVED: Check if file content is passed from upload with better processing
if (isset($_GET['file_content']) && !empty($_GET['file_content'])) {
    $file_content = urldecode($_GET['file_content']);
    
    // Enhanced content processing
    $module_content = processUploadedContent($file_content);
}

// Check if we're editing an existing module
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_mode = true;
    $module_id = intval($_GET['edit']);
    
    // Fetch module data from database
    $sql = "SELECT * FROM learning_modules WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $module_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $module_data = $result->fetch_assoc();
        $module_title = $module_data['title'] ?? '';
        $module_topic = $module_data['topic'] ?? '';
        $module_department = $module_data['department'] ?? '';
        $module_role = $module_data['roles'] ?? '';
        $module_content = $module_data['content'] ?? '';
        $module_status = $module_data['status'] ?? 'pending';
    } else {
        $_SESSION['error_message'] = "Module not found";
        header("Location: learning_module_repository.php");
        exit();
    }
    $stmt->close();
} else {
    // Get module info from URL parameters for new module
    $module_title = $_GET['title'] ?? '';
    $module_topic = $_GET['topic'] ?? '';
    $module_department = $_GET['department'] ?? '';
    $module_role = $_GET['role'] ?? '';
    $module_status = 'pending';
}

// Function to process uploaded content with better formatting
function processUploadedContent($content) {
    // If content appears to be HTML, process it
    if (strpos($content, '<') !== false && strpos($content, '>') !== false) {
        return processHtmlContent($content);
    }
    
    // If content is plain text with line breaks, preserve them
    if (strpos($content, "\n") !== false) {
        return nl2br(htmlspecialchars($content));
    }
    
    // Default: return as plain text with HTML escaping
    return htmlspecialchars($content);
}

// Function to process HTML content while preserving structure
function processHtmlContent($html) {
    // Create a DOM document
    $dom = new DOMDocument();
    
    // Suppress warnings for malformed HTML
    libxml_use_internal_errors(true);
    
    // Load HTML content
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    
    // Clear errors
    libxml_clear_errors();
    
    // Remove script and style elements
    $scripts = $dom->getElementsByTagName('script');
    $styles = $dom->getElementsByTagName('style');
    
    foreach ($scripts as $script) {
        $script->parentNode->removeChild($script);
    }
    
    foreach ($styles as $style) {
        $style->parentNode->removeChild($style);
    }
    
    // Extract body content if it exists
    $body = $dom->getElementsByTagName('body')->item(0);
    if ($body) {
        $content = '';
        foreach ($body->childNodes as $node) {
            $content .= $dom->saveHTML($node);
        }
        return $content;
    }
    
    // If no body tag, return the entire content
    return $dom->saveHTML();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? $module_title;
    $topic = $_POST['topic'] ?? $module_topic;
    $department = $_POST['department'] ?? $module_department;
    $roles = $_POST['roles'] ?? $module_role;
    $content = $_POST['content'] ?? $module_content;
    
    if ($edit_mode && $module_id) {
        // Update existing module
        $sql = "UPDATE learning_modules SET title = ?, topic = ?, department = ?, roles = ?, content = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $title, $topic, $department, $roles, $content, $module_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Module updated successfully!";
            // Clear localStorage after successful save
            echo '<script>localStorage.removeItem("module_draft_content");</script>';
            header("Location: learning_module_repository.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error updating module: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Insert new module
        $sql = "INSERT INTO learning_modules (title, topic, department, roles, content, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $title, $topic, $department, $roles, $content);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Module created successfully!";
            // Clear localStorage after successful save
            echo '<script>localStorage.removeItem("module_draft_content");</script>';
            header("Location: learning_module_repository.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error creating module: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Generate a unique storage key for this module/session
$storage_key = "module_draft_content";
if ($edit_mode && $module_id) {
    $storage_key .= "_" . $module_id;
} else {
    $storage_key .= "_new_" . md5($module_title . $module_topic . $module_department . $module_role);
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HR Portal - Document Editor</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../CSS/soliera.css">
  <link rel="stylesheet" href="../CSS/sidebar.css">
  <style>
    /* Professional Document Editor Styles */
    .document-editor {
      background: #f8f9fa;
      min-height: calc(100vh - 140px);
      padding: 20px;
    }

    .page-container {
      max-width: 8.5in;
      margin: 0 auto;
      background: white;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      min-height: 11in;
      position: relative;
    }

    .page-margins {
      padding: 1in;
      min-height: 11in;
      position: relative;
    }

    .page-break {
      page-break-after: always;
      border-bottom: 1px dashed #ccc;
      margin: 20px 0;
    }

    .editor-content {
      min-height: 9in;
      outline: none;
      font-family: 'Calibri', 'Arial', sans-serif;
      font-size: 11pt;
      line-height: 1.15;
      color: #000;
    }

    .editor-content p {
      margin-bottom: 12px;
    }

    .editor-content h1, 
    .editor-content h2, 
    .editor-content h3 {
      margin: 20px 0 12px 0;
      font-weight: bold;
    }

    .editor-content h1 { font-size: 16pt; }
    .editor-content h2 { font-size: 14pt; }
    .editor-content h3 { font-size: 12pt; }

    .editor-content ul, 
    .editor-content ol {
      margin: 12px 0;
      padding-left: 40px;
    }

    .editor-content li {
      margin-bottom: 6px;
    }

    /* Toolbar styling */
    .toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      padding: 0.75rem;
      background-color: #f8f9fa;
      border-bottom: 1px solid #e5e7eb;
      align-items: center;
      justify-content: center;
    }

    .toolbar-group {
      display: flex;
      gap: 0.25rem;
      align-items: center;
      padding-right: 0.75rem;
      border-right: 1px solid #d1d5db;
    }

    .toolbar-group:last-child {
      border-right: none;
    }

    .toolbar-btn {
      background: white;
      border: 1px solid #d1d5db;
      border-radius: 4px;
      padding: 0.5rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      transition: all 0.2s;
    }

    .toolbar-btn:hover {
      background-color: #f3f4f6;
      border-color: #9ca3af;
    }

    .toolbar-btn.active {
      background-color: #e5e7eb;
      border-color: #6b7280;
    }

    .toolbar-select {
      border: 1px solid #d1d5db;
      border-radius: 4px;
      padding: 0.5rem;
      background: white;
      cursor: pointer;
      font-size: 0.875rem;
      min-width: 100px;
    }

    /* Status bar */
    .status-bar {
      display: flex;
      justify-content: space-between;
      padding: 0.5rem 1rem;
      background-color: #f8f9fa;
      border-top: 1px solid #e5e7eb;
      font-size: 0.875rem;
      color: #6b7280;
    }

    .status-left, .status-right {
      display: flex;
      gap: 1rem;
    }

    .status-item {
      display: flex;
      align-items: center;
    }

    /* Header actions */
    .header-actions {
      display: flex;
      gap: 0.5rem;
      align-items: center;
    }
    
    .btn-primary {
      background-color: #3b82f6;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 0.375rem;
      border: none;
      cursor: pointer;
      font-size: 0.875rem;
      transition: background-color 0.2s;
      display: flex;
      align-items: center;
    }
    
    .btn-primary:hover {
      background-color: #2563eb;
    }
    
    .btn-secondary {
      background-color: #6b7280;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 0.375rem;
      border: none;
      cursor: pointer;
      font-size: 0.875rem;
      transition: background-color 0.2s;
      display: flex;
      align-items: center;
    }
    
    .btn-secondary:hover {
      background-color: #4b5563;
    }
    
    .btn-draft {
      background-color: #f59e0b;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 0.375rem;
      border: none;
      cursor: pointer;
      font-size: 0.875rem;
      transition: background-color 0.2s;
      display: flex;
      align-items: center;
    }
    
    .btn-draft:hover {
      background-color: #d97706;
    }

    /* Module info */
    .module-info {
      background: white;
      padding: 1.5rem;
      border-bottom: 1px solid #e5e7eb;
    }

    .module-info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
    }

    .info-item {
      display: flex;
      flex-direction: column;
    }

    .info-label {
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.25rem;
      font-size: 0.875rem;
    }

    .info-value {
      color: #6b7280;
      font-size: 0.875rem;
      padding: 0.5rem;
      background: #f9fafb;
      border-radius: 0.375rem;
    }

    /* Auto-save indicator */
    .auto-save-indicator {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background-color: #10b981;
      color: white;
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      opacity: 0;
      transition: opacity 0.3s ease;
      z-index: 1000;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .auto-save-indicator.show {
      opacity: 1;
    }

    /* Theme toggle */
    .theme-toggle {
      position: fixed;
      bottom: 70px;
      right: 20px;
      background-color: #3b82f6;
      color: white;
      border: none;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      z-index: 1000;
    }

    .theme-toggle:hover {
      background-color: #2563eb;
    }

    /* Dark mode adjustments */
    [data-theme="dark"] .document-editor {
      background-color: #374151;
    }

    [data-theme="dark"] .page-container {
      background-color: #1f2937;
      box-shadow: 0 0 10px rgba(0,0,0,0.3);
    }

    [data-theme="dark"] .editor-content {
      color: #f9fafb;
    }

    [data-theme="dark"] .toolbar {
      background-color: #4b5563;
      border-color: #6b7280;
    }

    [data-theme="dark"] .toolbar-btn {
      background-color: #6b7280;
      border-color: #9ca3af;
      color: #f9fafb;
    }

    [data-theme="dark"] .toolbar-btn:hover {
      background-color: #4b5563;
    }

    [data-theme="dark"] .toolbar-select {
      background-color: #6b7280;
      border-color: #9ca3af;
      color: #f9fafb;
    }

    [data-theme="dark"] .status-bar {
      background-color: #4b5563;
      border-color: #6b7280;
      color: #f9fafb;
    }

    [data-theme="dark"] .module-info {
      background-color: #374151;
      border-color: #4b5563;
    }

    [data-theme="dark"] .info-value {
      background-color: #4b5563;
      color: #d1d5db;
    }

    /* Alert styling */
    .alert {
      position: relative;
      padding: 0.75rem 1.25rem;
      margin: 1rem;
      border: 1px solid transparent;
      border-radius: 0.25rem;
    }

    .alert-success {
      color: #155724;
      background-color: #d4edda;
      border-color: #c3e6cb;
    }

    .alert-error {
      color: #721c24;
      background-color: #f8d7da;
      border-color: #f5c6cb;
    }

    .alert .flex-1 {
      display: flex;
      align-items: center;
    }

    .alert svg {
      width: 1.25rem;
      height: 1.25rem;
    }

    /* SweetAlert2 Custom Styles */
    .swal2-popup {
      z-index: 10000 !important;
    }
    
    .swal2-confirm, .swal2-deny, .swal2-cancel {
      display: inline-block !important;
      visibility: visible !important;
      opacity: 1 !important;
    }
    
    .swal2-actions {
      display: flex !important;
      visibility: visible !important;
      opacity: 1 !important;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .page-margins {
        padding: 0.5in;
      }
      
      .toolbar {
        justify-content: flex-start;
        overflow-x: auto;
      }
      
      .header-actions {
        flex-wrap: wrap;
      }
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../USM/sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../USM/navbar.php'; ?>

        <!-- Success and Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <div class="flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mx-2 stroke-current" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <label><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></label>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <div class="flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-6 h-6 mx-2 stroke-current"> 
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <label><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></label>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Module Header -->
        <div class="module-info">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        <?php echo $edit_mode ? 'Edit Learning Module' : 'Create Learning Module'; ?>
                    </h1>
                    <?php if ($edit_mode): ?>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            Status: <?php echo ucfirst($module_status); ?>
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                            Status: Pending
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="header-actions">
                    <button type="button" class="btn-draft" id="saveDraftBtn">
                        <i class="fas fa-save mr-2"></i>
                        Save as Draft
                    </button>
                    <button type="button" class="btn-primary" id="saveModuleBtn">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $edit_mode ? 'Update Module' : 'Save Module'; ?>
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='learning_module_repository.php'">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </button>
                </div>
            </div>
            
            <div class="module-info-grid">
                <div class="info-item">
                    <span class="info-label">Title</span>
                    <div class="info-value">
                        <?php echo !empty($module_title) ? htmlspecialchars($module_title) : 'No title set'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-label">Topic</span>
                    <div class="info-value">
                        <?php echo !empty($module_topic) ? htmlspecialchars($module_topic) : 'No topic set'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-label">Department</span>
                    <div class="info-value">
                        <?php echo !empty($module_department) ? htmlspecialchars(ucfirst(str_replace('-', ' ', $module_department))) : 'No department set'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-label">Role</span>
                    <div class="info-value">
                        <?php echo !empty($module_role) ? htmlspecialchars($module_role) : 'No role set'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <form method="POST" action="" id="moduleForm">
            <!-- Hidden fields to preserve the data -->
            <input type="hidden" name="title" value="<?php echo htmlspecialchars($module_title); ?>">
            <input type="hidden" name="topic" value="<?php echo htmlspecialchars($module_topic); ?>">
            <input type="hidden" name="department" value="<?php echo htmlspecialchars($module_department); ?>">
            <input type="hidden" name="roles" value="<?php echo htmlspecialchars($module_role); ?>">
            <input type="hidden" name="content" id="moduleContent" value="<?php echo htmlspecialchars($module_content); ?>">
            
            <?php if ($edit_mode && $module_id): ?>
                <input type="hidden" name="module_id" value="<?php echo $module_id; ?>">
            <?php endif; ?>
        </form>

        <!-- Document Editor -->
        <div class="document-editor">
            <!-- Toolbar -->
            <div class="toolbar">
                <div class="toolbar-group">
                    <button class="toolbar-btn" id="saveBtn" title="Save">
                        <i class="fas fa-save"></i>
                    </button>
                    <button class="toolbar-btn" id="printBtn" title="Print">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
                
                <div class="toolbar-group">
                    <button class="toolbar-btn" id="undoBtn" title="Undo">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button class="toolbar-btn" id="redoBtn" title="Redo">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
                
                <div class="toolbar-group">
                    <select class="toolbar-select" id="fontFamily">
                        <option value="Arial">Arial</option>
                        <option value="Calibri" selected>Calibri</option>
                        <option value="Times New Roman">Times New Roman</option>
                        <option value="Georgia">Georgia</option>
                        <option value="Verdana">Verdana</option>
                    </select>
                    <select class="toolbar-select" id="fontSize">
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                        <option value="11" selected>11</option>
                        <option value="12">12</option>
                        <option value="14">14</option>
                        <option value="16">16</option>
                        <option value="18">18</option>
                        <option value="20">20</option>
                        <option value="24">24</option>
                    </select>
                </div>
                
                <div class="toolbar-group">
                    <button class="toolbar-btn" id="boldBtn" title="Bold">
                        <i class="fas fa-bold"></i>
                    </button>
                    <button class="toolbar-btn" id="italicBtn" title="Italic">
                        <i class="fas fa-italic"></i>
                    </button>
                    <button class="toolbar-btn" id="underlineBtn" title="Underline">
                        <i class="fas fa-underline"></i>
                    </button>
                </div>
                
                <div class="toolbar-group">
                    <button class="toolbar-btn" id="alignLeftBtn" title="Align Left">
                        <i class="fas fa-align-left"></i>
                    </button>
                    <button class="toolbar-btn" id="alignCenterBtn" title="Align Center">
                        <i class="fas fa-align-center"></i>
                    </button>
                    <button class="toolbar-btn" id="alignRightBtn" title="Align Right">
                        <i class="fas fa-align-right"></i>
                    </button>
                    <button class="toolbar-btn" id="alignJustifyBtn" title="Justify">
                        <i class="fas fa-align-justify"></i>
                    </button>
                </div>
                
                <div class="toolbar-group">
                    <button class="toolbar-btn" id="bulletListBtn" title="Bullet List">
                        <i class="fas fa-list-ul"></i>
                    </button>
                    <button class="toolbar-btn" id="numberListBtn" title="Numbered List">
                        <i class="fas fa-list-ol"></i>
                    </button>
                    <button class="toolbar-btn" id="indentBtn" title="Increase Indent">
                        <i class="fas fa-indent"></i>
                    </button>
                    <button class="toolbar-btn" id="outdentBtn" title="Decrease Indent">
                        <i class="fas fa-outdent"></i>
                    </button>
                </div>
                
                <div class="toolbar-group">
                    <input type="color" class="toolbar-btn" id="textColor" title="Text Color" value="#000000" style="padding: 2px;">
                    <button class="toolbar-btn" id="highlightColorBtn" title="Highlight Color">
                        <i class="fas fa-highlighter"></i>
                    </button>
                </div>
            </div>

            <!-- Page Container -->
            <div class="page-container">
                <div class="page-margins">
                    <div class="editor-content" id="editor" contenteditable="true">
                        <?php echo !empty($module_content) ? $module_content : '<p>Start typing your document here...</p>'; ?>
                    </div>
                </div>
            </div>

            <!-- Status Bar -->
            <div class="status-bar">
                <div class="status-left">
                    <span class="status-item" id="pageInfo">Page 1 of 1</span>
                    <span class="status-item" id="wordCount">Words: 0</span>
                    <span class="status-item" id="charCount">Characters: 0</span>
                    <span class="status-item" id="autoSaveStatus">Auto-save: On</span>
                </div>
                <div class="status-right">
                    <span class="status-item" id="zoomLevel">100%</span>
                </div>
            </div>
        </div>

        <!-- Auto-save Indicator -->
        <div class="auto-save-indicator" id="autoSaveIndicator">
            <i class="fas fa-check-circle"></i> Draft saved
        </div>

        <!-- Theme Toggle Button -->
        <button class="theme-toggle" id="themeToggle">
            <i class="fas fa-moon"></i>
        </button>
    </div>
  </div>

  <!-- HTML-to-PDF library (client-side) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>

  <!-- Custom JS -->
  <script>
  document.addEventListener('DOMContentLoaded', function() {
      const editor = document.getElementById('editor');
      const moduleContent = document.getElementById('moduleContent');
      const saveModuleBtn = document.getElementById('saveModuleBtn');
      const saveDraftBtn = document.getElementById('saveDraftBtn');
      const moduleForm = document.getElementById('moduleForm');
      const themeToggle = document.getElementById('themeToggle');
      const wordCountElement = document.getElementById('wordCount');
      const charCountElement = document.getElementById('charCount');
      const autoSaveStatusElement = document.getElementById('autoSaveStatus');
      const autoSaveIndicator = document.getElementById('autoSaveIndicator');
      const pageInfoElement = document.getElementById('pageInfo');
      
      // Toolbar elements
      const fontFamily = document.getElementById('fontFamily');
      const fontSize = document.getElementById('fontSize');
      const boldBtn = document.getElementById('boldBtn');
      const italicBtn = document.getElementById('italicBtn');
      const underlineBtn = document.getElementById('underlineBtn');
      const alignLeftBtn = document.getElementById('alignLeftBtn');
      const alignCenterBtn = document.getElementById('alignCenterBtn');
      const alignRightBtn = document.getElementById('alignRightBtn');
      const alignJustifyBtn = document.getElementById('alignJustifyBtn');
      const bulletListBtn = document.getElementById('bulletListBtn');
      const numberListBtn = document.getElementById('numberListBtn');
      const indentBtn = document.getElementById('indentBtn');
      const outdentBtn = document.getElementById('outdentBtn');
      const textColor = document.getElementById('textColor');
      const highlightColorBtn = document.getElementById('highlightColorBtn');
      const saveBtn = document.getElementById('saveBtn');
      const printBtn = document.getElementById('printBtn');
      const undoBtn = document.getElementById('undoBtn');
      const redoBtn = document.getElementById('redoBtn');
      
      let autoSaveTimer;
      let isAutoSaving = false;
      
      // Storage key for this specific module
      const storageKey = '<?php echo $storage_key; ?>';
      
      // Initialize editor
      function initEditor() {
          // Load saved content from localStorage
          loadSavedContent();
          
          // Set default font and size
          editor.style.fontFamily = fontFamily.value;
          editor.style.fontSize = fontSize.value + 'pt';
          
          // Update word count
          updateWordCount();
          updatePageInfo();
          
          // Set up event listeners
          setupEventListeners();
          
          // Set up toolbar functionality
          setupToolbar();
          
          // Set up theme toggle
          setupThemeToggle();
          
          // Set up save as draft button
          setupSaveDraftButton();
          
          // Focus the editor
          editor.focus();
      }
      
      // Load saved content from localStorage
      function loadSavedContent() {
          const savedContent = localStorage.getItem(storageKey);
          if (savedContent && savedContent !== editor.innerHTML) {
              if (!<?php echo $edit_mode ? 'true' : 'false'; ?> || !editor.innerHTML.trim() || editor.innerHTML.includes('Start typing your document here')) {
                  editor.innerHTML = savedContent;
                  console.log('Loaded saved content from localStorage');
              }
          }
      }
      
      // Set up event listeners
      function setupEventListeners() {
          // Editor events
          editor.addEventListener('input', function() {
              updateWordCount();
              updatePageInfo();
              scheduleAutoSave();
          });
          
          editor.addEventListener('keydown', function(e) {
              // Handle keyboard shortcuts
              if (e.ctrlKey || e.metaKey) {
                  switch(e.key) {
                      case 'a':
                          e.preventDefault();
                          document.execCommand('selectAll');
                          break;
                      case 'c':
                          // Allow default copy behavior
                          break;
                      case 'v':
                          // Allow default paste behavior
                          setTimeout(() => {
                              updateWordCount();
                              scheduleAutoSave();
                          }, 10);
                          break;
                      case 'x':
                          // Allow default cut behavior
                          setTimeout(() => {
                              updateWordCount();
                              scheduleAutoSave();
                          }, 10);
                          break;
                      case 'z':
                          e.preventDefault();
                          if (e.shiftKey) {
                              document.execCommand('redo');
                          } else {
                              document.execCommand('undo');
                          }
                          scheduleAutoSave();
                          break;
                      case 'b':
                          e.preventDefault();
                          document.execCommand('bold');
                          updateToolbarStates();
                          scheduleAutoSave();
                          break;
                      case 'i':
                          e.preventDefault();
                          document.execCommand('italic');
                          updateToolbarStates();
                          scheduleAutoSave();
                          break;
                      case 'u':
                          e.preventDefault();
                          document.execCommand('underline');
                          updateToolbarStates();
                          scheduleAutoSave();
                          break;
                  }
              }
          });
          
          // Auto-save on blur
          editor.addEventListener('blur', function() {
              saveToLocalStorage();
          });
          
          // Update toolbar states when selection changes
          document.addEventListener('selectionchange', updateToolbarStates);
      }
      
      // Set up toolbar functionality
      function setupToolbar() {
          // Font family
          fontFamily.addEventListener('change', function() {
              document.execCommand('fontName', false, this.value);
              editor.style.fontFamily = this.value;
              scheduleAutoSave();
          });
          
          // Font size
          fontSize.addEventListener('change', function() {
              document.execCommand('fontSize', false, '7');
              const selection = window.getSelection();
              if (selection.rangeCount > 0) {
                  const range = selection.getRangeAt(0);
                  const span = document.createElement('span');
                  span.style.fontSize = this.value + 'pt';
                  range.surroundContents(span);
              }
              scheduleAutoSave();
          });
          
          // Text formatting
          boldBtn.addEventListener('click', function() {
              document.execCommand('bold', false, null);
              scheduleAutoSave();
          });
          
          italicBtn.addEventListener('click', function() {
              document.execCommand('italic', false, null);
              scheduleAutoSave();
          });
          
          underlineBtn.addEventListener('click', function() {
              document.execCommand('underline', false, null);
              scheduleAutoSave();
          });
          
          // Text alignment
          alignLeftBtn.addEventListener('click', function() {
              document.execCommand('justifyLeft', false, null);
              updateActiveAlignmentButton(this);
              scheduleAutoSave();
          });
          
          alignCenterBtn.addEventListener('click', function() {
              document.execCommand('justifyCenter', false, null);
              updateActiveAlignmentButton(this);
              scheduleAutoSave();
          });
          
          alignRightBtn.addEventListener('click', function() {
              document.execCommand('justifyRight', false, null);
              updateActiveAlignmentButton(this);
              scheduleAutoSave();
          });
          
          alignJustifyBtn.addEventListener('click', function() {
              document.execCommand('justifyFull', false, null);
              updateActiveAlignmentButton(this);
              scheduleAutoSave();
          });
          
          // Lists and indentation
          bulletListBtn.addEventListener('click', function() {
              document.execCommand('insertUnorderedList', false, null);
              scheduleAutoSave();
          });
          
          numberListBtn.addEventListener('click', function() {
              document.execCommand('insertOrderedList', false, null);
              scheduleAutoSave();
          });
          
          indentBtn.addEventListener('click', function() {
              document.execCommand('indent', false, null);
              scheduleAutoSave();
          });
          
          outdentBtn.addEventListener('click', function() {
              document.execCommand('outdent', false, null);
              scheduleAutoSave();
          });
          
          // Colors
          textColor.addEventListener('input', function() {
              document.execCommand('foreColor', false, this.value);
              scheduleAutoSave();
          });
          
          highlightColorBtn.addEventListener('click', function() {
              document.execCommand('hiliteColor', false, '#ffff00');
              scheduleAutoSave();
          });
          
          // Save and print
          saveBtn.addEventListener('click', function() {
              saveContent();
              Swal.fire({
                  title: 'Saved!',
                  text: 'Your content has been saved.',
                  icon: 'success',
                  confirmButtonText: 'OK'
              });
          });
          
          printBtn.addEventListener('click', function() {
              window.print();
          });
          
          // Undo/redo
          undoBtn.addEventListener('click', function() {
              document.execCommand('undo', false, null);
              scheduleAutoSave();
          });
          
          redoBtn.addEventListener('click', function() {
              document.execCommand('redo', false, null);
              scheduleAutoSave();
          });
      }
      
      // Set up theme toggle
      function setupThemeToggle() {
          themeToggle.addEventListener('click', function() {
              const currentTheme = document.documentElement.getAttribute('data-theme');
              const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
              document.documentElement.setAttribute('data-theme', newTheme);
              
              const icon = this.querySelector('i');
              if (newTheme === 'dark') {
                  icon.className = 'fas fa-sun';
              } else {
                  icon.className = 'fas fa-moon';
              }
          });
      }
      
      // Set up save as draft button
      function setupSaveDraftButton() {
          saveDraftBtn.addEventListener('click', function() {
              saveContent();
              
              const title = document.querySelector('input[name="title"]').value || 'Untitled Module';
              const topic = document.querySelector('input[name="topic"]').value || '';
              const department = document.querySelector('input[name="department"]').value || '';
              const role = document.querySelector('input[name="roles"]').value || '';
              const content = moduleContent.value;
              
              // Show loading state
              const originalText = saveDraftBtn.innerHTML;
              saveDraftBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
              saveDraftBtn.disabled = true;
              
              // Save as draft via AJAX
              saveModuleAsDraft(title, topic, department, role, content)
                  .then(result => {
                      if (result.success) {
                          // Clear localStorage after successful draft save
                          localStorage.removeItem(storageKey);
                          
                          // Show success message
                          Swal.fire({
                              title: 'Saved as Draft!',
                              text: 'Your module has been saved to drafts. You can continue editing it later.',
                              icon: 'success',
                              confirmButtonText: 'OK'
                          }).then((result) => {
                              if (result.isConfirmed) {
                                  window.location.href = 'learning_module_repository.php';
                              }
                          });
                      } else {
                          throw new Error(result.message);
                      }
                  })
                  .catch(error => {
                      console.error('Error saving draft:', error);
                      Swal.fire({
                          title: 'Error!',
                          text: 'Failed to save draft: ' + error.message,
                          icon: 'error',
                          confirmButtonText: 'OK'
                      });
                  })
                  .finally(() => {
                      // Restore button state
                      saveDraftBtn.innerHTML = originalText;
                      saveDraftBtn.disabled = false;
                  });
          });
      }
      
      // Function to save module as draft via AJAX
      function saveModuleAsDraft(title, topic, department, role, content) {
          const formData = new FormData();
          formData.append('save_draft', '1');
          formData.append('title', title);
          formData.append('topic', topic);
          formData.append('department', department);
          formData.append('role', role);
          formData.append('content', content);
          formData.append('ajax', '1');
          
          <?php if ($edit_mode && $module_id): ?>
          formData.append('draft_id', <?php echo $module_id; ?>);
          <?php endif; ?>

          return fetch('learning_module_repository.php', {
              method: 'POST',
              body: formData
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  return data;
              } else {
                  throw new Error(data.message);
              }
          });
      }
      
      // Update toolbar button states
      function updateToolbarStates() {
          boldBtn.classList.toggle('active', document.queryCommandState('bold'));
          italicBtn.classList.toggle('active', document.queryCommandState('italic'));
          underlineBtn.classList.toggle('active', document.queryCommandState('underline'));
          
          // Update alignment buttons
          if (document.queryCommandState('justifyLeft')) {
              updateActiveAlignmentButton(alignLeftBtn);
          } else if (document.queryCommandState('justifyCenter')) {
              updateActiveAlignmentButton(alignCenterBtn);
          } else if (document.queryCommandState('justifyRight')) {
              updateActiveAlignmentButton(alignRightBtn);
          } else if (document.queryCommandState('justifyFull')) {
              updateActiveAlignmentButton(alignJustifyBtn);
          }
      }
      
      // Update active alignment button
      function updateActiveAlignmentButton(activeButton) {
          const alignmentButtons = [alignLeftBtn, alignCenterBtn, alignRightBtn, alignJustifyBtn];
          alignmentButtons.forEach(btn => btn.classList.remove('active'));
          activeButton.classList.add('active');
      }
      
      // Update word and character count
      function updateWordCount() {
          const text = editor.innerText || editor.textContent;
          const words = text.trim() ? text.trim().split(/\s+/).length : 0;
          const characters = text.length;
          
          wordCountElement.textContent = `Words: ${words}`;
          charCountElement.textContent = `Characters: ${characters}`;
      }
      
      // Update page information
      function updatePageInfo() {
          // Simple page estimation based on content height
          const contentHeight = editor.scrollHeight;
          const pageHeight = 792; // 11in in points (72 * 11)
          const pages = Math.max(1, Math.ceil(contentHeight / pageHeight));
          pageInfoElement.textContent = `Page 1 of ${pages}`;
      }
      
      // Auto-save functionality
      function scheduleAutoSave() {
          if (isAutoSaving) return;
          
          clearTimeout(autoSaveTimer);
          autoSaveTimer = setTimeout(saveToLocalStorage, 2000);
      }
      
      function saveToLocalStorage() {
          isAutoSaving = true;
          const content = editor.innerHTML;
          localStorage.setItem(storageKey, content);
          
          // Show auto-save indicator
          autoSaveIndicator.classList.add('show');
          setTimeout(() => {
              autoSaveIndicator.classList.remove('show');
          }, 2000);
          
          console.log('Auto-saved content to localStorage');
          isAutoSaving = false;
      }
      
      // Save content to hidden field
      function saveContent() {
          moduleContent.value = editor.innerHTML;
          saveToLocalStorage();
      }
      
      // Initialize the editor
      initEditor();
      
      // Set up save module button
      saveModuleBtn.addEventListener('click', function(e) {
          e.preventDefault();
          saveContent();
          
          const actionText = <?php echo $edit_mode ? "'update'" : "'save'"; ?>;
          const actionTextCapitalized = <?php echo $edit_mode ? "'Update'" : "'Save'"; ?>;
          
          Swal.fire({
              title: actionTextCapitalized + ' Module',
              text: 'Do you want to (1) ' + actionText + ' to repository only, (2) ' + actionText + ' and download as DOC, or (3) ' + actionText + ' and download as PDF?',
              icon: 'question',
              showDenyButton: true,
              showCancelButton: true,
              confirmButtonText: actionTextCapitalized + ' only',
              denyButtonText: actionTextCapitalized + ' & Download DOC',
              cancelButtonText: actionTextCapitalized + ' & Download PDF'
          }).then((result) => {
              if (result.isConfirmed) {
                  localStorage.removeItem(storageKey);
                  moduleForm.submit();
              } else if (result.isDenied) {
                  const content = moduleContent.value;
                  downloadDocFromHtml(content, '<?php echo addslashes($module_title ?: "Learning_Module"); ?>');
                  setTimeout(() => {
                      localStorage.removeItem(storageKey);
                      moduleForm.submit();
                  }, 800);
              } else if (result.dismiss === Swal.DismissReason.cancel) {
                  const content = moduleContent.value;
                  downloadPdfFromHtml(content, '<?php echo addslashes($module_title ?: "Learning_Module"); ?>');
                  setTimeout(() => {
                      localStorage.removeItem(storageKey);
                      moduleForm.submit();
                  }, 1200);
              }
          });
      });
      
      // Download as DOC
      function downloadDocFromHtml(htmlContent, baseName) {
          const header = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Document</title></head><body>';
          const footer = '</body></html>';
          const blob = new Blob([header + htmlContent + footer], { type: 'application/msword' });
          const fileName = baseName.replace(/\s+/g,'_') + '_' + new Date().toISOString().slice(0,19).replace(/[:T]/g,'-') + '.doc';
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = fileName;
          document.body.appendChild(link);
          link.click();
          link.remove();
      }
      
      // Download as PDF
      function downloadPdfFromHtml(htmlContent, baseName) {
          const tempDiv = document.createElement('div');
          tempDiv.style.position = 'fixed';
          tempDiv.style.left = '-10000px';
          tempDiv.style.top = '0';
          tempDiv.innerHTML = htmlContent;
          document.body.appendChild(tempDiv);
          
          const opt = {
              margin:       10,
              filename:     baseName.replace(/\s+/g,'_') + '_' + new Date().toISOString().slice(0,19).replace(/[:T]/g,'-') + '.pdf',
              image:        { type: 'jpeg', quality: 0.98 },
              html2canvas:  { scale: 2, logging: false, useCORS: true },
              jsPDF:        { unit: 'pt', format: 'a4', orientation: 'portrait' }
          };
          
          html2pdf().set(opt).from(tempDiv).save()
          .finally(() => {
              tempDiv.remove();
          });
      }

      // Save content before page unload
      window.addEventListener('beforeunload', function(e) {
          if (editor.innerHTML && !editor.innerHTML.includes('Start typing your document here')) {
              saveToLocalStorage();
          }
      });
  });
  </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>