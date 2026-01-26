<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Recognition Dashboard | HospitalityPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0864a6;
            --secondary-color: #d4af37;
            --light-color: #f8f9fa;
            --dark-color: #333;
            --success-color: #28a745;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
        }

        /* Header Section */
        .header {
            background-color: white;
            box-shadow: var(--card-shadow);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            color: var(--primary-color);
            font-size: 1.8rem;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .employee-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .employee-details {
            text-align: right;
        }

        .employee-name {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .employee-position {
            font-size: 0.9rem;
            color: #666;
        }

        .employee-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .notification-container {
            position: relative;
        }

        .notification-bell {
            font-size: 1.4rem;
            color: #666;
            cursor: pointer;
            position: relative;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #e74c3c;
            color: white;
            font-size: 0.7rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logout-btn {
            background-color: transparent;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            color: #666;
            transition: var(--transition);
        }

        .logout-btn:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
        }

        /* Main Content */
        .main-container {
            margin-top: 70px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .dashboard-title {
            margin-bottom: 1rem;
            color: var(--primary-color);
            font-size: 1.8rem;
        }

        /* Layout Grid */
        .dashboard-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
        }

        .summary-card {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 1.2rem;
            transition: var(--transition);
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .icon-1 { background-color: rgba(8, 100, 166, 0.1); color: var(--primary-color); }
        .icon-2 { background-color: rgba(212, 175, 55, 0.1); color: var(--secondary-color); }
        .icon-3 { background-color: rgba(40, 167, 69, 0.1); color: var(--success-color); }
        .icon-4 { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; }

        .card-content h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .card-content p {
            color: #666;
            font-size: 0.95rem;
        }

        /* Dashboard Sections */
        .dashboard-section {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.2rem;
            color: var(--primary-color);
            font-size: 1.3rem;
        }

        .section-title i {
            color: var(--secondary-color);
        }

        /* Recognition Table */
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .search-box {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 12px;
            width: 300px;
        }

        .search-box input {
            border: none;
            outline: none;
            width: 100%;
            padding-left: 8px;
        }

        .filter-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            color: #555;
        }

        .table-container {
            overflow-x: auto;
        }

        .recognition-table {
            width: 100%;
            border-collapse: collapse;
        }

        .recognition-table th {
            background-color: #f8f9fa;
            padding: 0.9rem;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #eee;
        }

        .recognition-table td {
            padding: 0.9rem;
            border-bottom: 1px solid #eee;
        }

        .recognition-table tr:hover {
            background-color: #f8f9fa;
        }

        .category-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }

        .badge-award { background-color: rgba(212, 175, 55, 0.15); color: #b8941c; }
        .badge-certificate { background-color: rgba(8, 100, 166, 0.15); color: var(--primary-color); }
        .badge-achievement { background-color: rgba(40, 167, 69, 0.15); color: var(--success-color); }
        .badge-commendation { background-color: rgba(108, 117, 125, 0.15); color: #6c757d; }

        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-verified { background-color: rgba(40, 167, 69, 0.15); color: var(--success-color); }
        .status-pending { background-color: rgba(255, 193, 7, 0.15); color: #e0a800; }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .view-btn { background-color: rgba(8, 100, 166, 0.1); color: var(--primary-color); }
        .download-btn { background-color: rgba(40, 167, 69, 0.1); color: var(--success-color); }
        .share-btn { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; }

        .action-btn:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }

        /* Achievement Timeline - Sidebar Version */
        .timeline-container {
            position: relative;
            padding-left: 1.5rem;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
            padding-left: 1.2rem;
            border-left: 2px solid #e9ecef;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: var(--secondary-color);
        }

        .timeline-date {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.4rem;
            font-weight: 500;
        }

        .timeline-content {
            background-color: #f8f9fa;
            padding: 0.8rem;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .timeline-content h4 {
            color: var(--primary-color);
            margin-bottom: 0.4rem;
            font-size: 1rem;
        }

        .timeline-content p {
            font-size: 0.85rem;
            color: #666;
            line-height: 1.4;
        }

        /* Categories and Badges Grid */
        .categories-badges-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        /* Categories Section - Smaller */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .category-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.2rem 1rem;
            text-align: center;
            transition: var(--transition);
            border: 1px solid #eee;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .category-card:hover {
            background-color: white;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-3px);
            border-color: var(--primary-color);
        }

        .category-icon {
            font-size: 2rem;
            margin-bottom: 0.8rem;
            color: var(--primary-color);
        }

        .category-count {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.3rem;
        }

        .category-name {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        /* Badge Showcase - Smaller */
        .badge-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
        }

        .badge-item {
            text-align: center;
            padding: 0.8rem;
            transition: var(--transition);
        }

        .badge-item:hover {
            transform: translateY(-5px);
        }

        .badge-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 0.8rem;
        }

        .badge-name {
            font-weight: 600;
            color: #555;
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }

        .badge-date {
            font-size: 0.75rem;
            color: #888;
        }

        /* Certificates Section */
        .certificates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .certificate-card {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: var(--transition);
        }

        .certificate-card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .certificate-preview {
            height: 140px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 2.5rem;
        }

        .certificate-info {
            padding: 1rem;
        }

        .certificate-info h4 {
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            font-size: 1rem;
        }

        .certificate-info p {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .certificate-actions {
            display: flex;
            gap: 10px;
        }

        .certificate-btn {
            padding: 6px 10px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            flex: 1;
            transition: var(--transition);
        }

        .view-cert-btn {
            background-color: rgba(8, 100, 166, 0.1);
            color: var(--primary-color);
        }

        .download-cert-btn {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .certificate-btn:hover {
            opacity: 0.8;
        }

        /* Notifications Panel */
        .notifications-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 0.9rem;
            border-bottom: 1px solid #eee;
            display: flex;
            gap: 0.8rem;
            align-items: flex-start;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(8, 100, 166, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.9rem;
        }

        .notification-content h5 {
            margin-bottom: 0.3rem;
            color: #333;
            font-size: 0.95rem;
        }

        .notification-content p {
            font-size: 0.85rem;
            color: #666;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #999;
            margin-top: 0.3rem;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .recognition-detail-item {
            margin-bottom: 1.2rem;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 0.3rem;
        }

        .detail-value {
            color: #333;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .modal-btn {
            padding: 10px 20px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }

        .download-modal-btn {
            background-color: var(--primary-color);
            color: white;
        }

        .close-modal-btn {
            background-color: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
        }

        .modal-btn:hover {
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 1.5rem;
            color: #666;
            font-size: 0.9rem;
            border-top: 1px solid #eee;
            margin-top: 2rem;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar-content {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 2rem;
            }
        }

        @media (max-width: 1024px) {
            .main-container {
                padding: 1.5rem;
            }
            
            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .categories-badges-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 0 1rem;
            }
            
            .employee-details {
                display: none;
            }
            
            .main-container {
                padding: 1rem;
            }
            
            .summary-cards {
                grid-template-columns: 1fr;
            }
            
            .table-controls {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
            }
            
            .categories-grid,
            .badge-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .sidebar-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                font-size: 1.2rem;
            }
            
            .categories-grid,
            .badge-container,
            .certificates-grid {
                grid-template-columns: 1fr;
            }
            
            .modal {
                width: 95%;
            }
        }
    </style>
</head>
<body class="bg-base-200 min-h-screen">
    <div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>
    <!-- Main Dashboard Content -->
    <main class="main-container">
        <h1 class="dashboard-title">My Recognition Dashboard</h1>
        
        <!-- Summary Cards -->
        <section class="summary-cards">
            <div class="summary-card">
                <div class="card-icon icon-1">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="card-content">
                    <h3>24</h3>
                    <p>Total Recognitions</p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon icon-2">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="card-content">
                    <h3>8</h3>
                    <p>Certificates Received</p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon icon-3">
                    <i class="fas fa-star"></i>
                </div>
                <div class="card-content">
                    <h3>5</h3>
                    <p>Awards & Honors</p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon icon-4">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-content">
                    <h3>12</h3>
                    <p>Recent Achievements</p>
                </div>
            </div>
        </section>
        
        <!-- Main Layout -->
        <div class="dashboard-layout">
            <div class="main-content">
                <!-- Recognition Records Table -->
                <section class="dashboard-section">
                    <h2 class="section-title">
                        <i class="fas fa-list-alt"></i> My Recognition Records
                    </h2>
                    
                    <div class="table-controls">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search recognitions...">
                        </div>
                        
                        <div class="filter-controls">
                            <select class="filter-select">
                                <option>All Categories</option>
                                <option>Awards</option>
                                <option>Certificates</option>
                                <option>Achievements</option>
                                <option>Commendations</option>
                            </select>
                            
                            <select class="filter-select">
                                <option>Sort by: Newest First</option>
                                <option>Sort by: Oldest First</option>
                                <option>Sort by: Recognition Type</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table class="recognition-table">
                            <thead>
                                <tr>
                                    <th>Recognition Title</th>
                                    <th>Category</th>
                                    <th>Issued By</th>
                                    <th>Date Awarded</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Employee of the Month - March</td>
                                    <td><span class="category-badge badge-award">Award</span></td>
                                    <td>Hotel Management</td>
                                    <td>Mar 15, 2024</td>
                                    <td><span class="status-badge status-verified">Verified</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn view-btn" onclick="openModal()">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn download-btn">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="action-btn share-btn">
                                                <i class="fas fa-share-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Food Safety Certification</td>
                                    <td><span class="category-badge badge-certificate">Certificate</span></td>
                                    <td>HR Department</td>
                                    <td>Feb 28, 2024</td>
                                    <td><span class="status-badge status-verified">Verified</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn view-btn">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn download-btn">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="action-btn share-btn">
                                                <i class="fas fa-share-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Perfect Attendance Q4 2023</td>
                                    <td><span class="category-badge badge-achievement">Achievement</span></td>
                                    <td>Department Manager</td>
                                    <td>Jan 10, 2024</td>
                                    <td><span class="status-badge status-verified">Verified</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn view-btn">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn download-btn">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="action-btn share-btn">
                                                <i class="fas fa-share-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Customer Service Excellence</td>
                                    <td><span class="category-badge badge-commendation">Commendation</span></td>
                                    <td>Hotel Manager</td>
                                    <td>Dec 5, 2023</td>
                                    <td><span class="status-badge status-verified">Verified</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn view-btn">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn download-btn">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="action-btn share-btn">
                                                <i class="fas fa-share-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <button style="padding: 8px 16px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
                            Load More Records
                        </button>
                    </div>
                </section>
                
                <!-- Categories and Badges Side by Side -->
                <section class="dashboard-section">
                    <h2 class="section-title">
                        <i class="fas fa-folder-open"></i> Recognition Overview
                    </h2>
                    
                    <div class="categories-badges-grid">
                        <!-- Categories Section -->
                        <div>
                            <h3 class="section-title" style="font-size: 1.1rem; margin-bottom: 1rem;">
                                <i class="fas fa-tags"></i> Categories
                            </h3>
                            <div class="categories-grid">
                                <div class="category-card">
                                    <div class="category-icon">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                    <div class="category-count">3</div>
                                    <div class="category-name">Employee of the Month</div>
                                </div>
                                
                                <div class="category-card">
                                    <div class="category-icon">
                                        <i class="fas fa-medal"></i>
                                    </div>
                                    <div class="category-count">2</div>
                                    <div class="category-name">Service Excellence</div>
                                </div>
                                
                                <div class="category-card">
                                    <div class="category-icon">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div class="category-count">8</div>
                                    <div class="category-name">Training & Certification</div>
                                </div>
                                
                                <div class="category-card">
                                    <div class="category-icon">
                                        <i class="fas fa-award"></i>
                                    </div>
                                    <div class="category-count">3</div>
                                    <div class="category-name">Loyalty & Service Years</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Badge Showcase -->
                        <div>
                            <h3 class="section-title" style="font-size: 1.1rem; margin-bottom: 1rem;">
                                <i class="fas fa-shield-alt"></i> Digital Badges
                            </h3>
                            <div class="badge-container">
                                <div class="badge-item">
                                    <div class="badge-icon">
                                        <i class="fas fa-smile"></i>
                                    </div>
                                    <div class="badge-name">Customer Service Star</div>
                                    <div class="badge-date">Mar 2024</div>
                                </div>
                                
                                <div class="badge-item">
                                    <div class="badge-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="badge-name">Perfect Attendance</div>
                                    <div class="badge-date">Jan 2024</div>
                                </div>
                                
                                <div class="badge-item">
                                    <div class="badge-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="badge-name">Top Performer</div>
                                    <div class="badge-date">Dec 2023</div>
                                </div>
                                
                                <div class="badge-item">
                                    <div class="badge-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="badge-name">Team Player</div>
                                    <div class="badge-date">Nov 2023</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Certificates Section -->
                <section class="dashboard-section">
                    <h2 class="section-title">
                        <i class="fas fa-file-certificate"></i> Certificates & Documents
                    </h2>
                    
                    <div class="certificates-grid">
                        <div class="certificate-card">
                            <div class="certificate-preview">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div class="certificate-info">
                                <h4>Food Safety Certification</h4>
                                <p>Issued: Feb 28, 2024 | Valid until: Feb 28, 2025</p>
                                <div class="certificate-actions">
                                    <button class="certificate-btn view-cert-btn">View</button>
                                    <button class="certificate-btn download-cert-btn">Download</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="certificate-card">
                            <div class="certificate-preview">
                                <i class="fas fa-award"></i>
                            </div>
                            <div class="certificate-info">
                                <h4>Employee of the Month</h4>
                                <p>Issued: Mar 15, 2024 | Category: Award</p>
                                <div class="certificate-actions">
                                    <button class="certificate-btn view-cert-btn">View</button>
                                    <button class="certificate-btn download-cert-btn">Download</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="certificate-card">
                            <div class="certificate-preview">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="certificate-info">
                                <h4>Hospitality Management</h4>
                                <p>Issued: Oct 10, 2023 | Category: Training</p>
                                <div class="certificate-actions">
                                    <button class="certificate-btn view-cert-btn">View</button>
                                    <button class="certificate-btn download-cert-btn">Download</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- Sidebar Content -->
            <div class="sidebar-content">
                <!-- Achievement Timeline -->
                <section class="dashboard-section">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i> Achievement Timeline
                    </h2>
                    
                    <div class="timeline-container">
                        <div class="timeline-item">
                            <div class="timeline-date">March 2024</div>
                            <div class="timeline-content">
                                <h4>Employee of the Month</h4>
                                <p>Awarded for exceptional customer service and positive guest feedback.</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-date">February 2024</div>
                            <div class="timeline-content">
                                <h4>Food Safety Certification</h4>
                                <p>Completed advanced food safety training and certification.</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-date">January 2024</div>
                            <div class="timeline-content">
                                <h4>Perfect Attendance - Q4 2023</h4>
                                <p>Recognized for perfect attendance in the last quarter of 2023.</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-date">December 2023</div>
                            <div class="timeline-content">
                                <h4>3-Year Service Anniversary</h4>
                                <p>Celebrating three years of dedicated service to the hotel.</p>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Notifications Panel -->
                <section class="dashboard-section">
                    <h2 class="section-title">
                        <i class="fas fa-bell"></i> Recent Notifications
                    </h2>
                    
                    <div class="notifications-list">
                        <div class="notification-item">
                            <div class="notification-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="notification-content">
                                <h5>New Award Received</h5>
                                <p>Congratulations! You've been awarded "Employee of the Month" for March 2024.</p>
                                <div class="notification-time">2 hours ago</div>
                            </div>
                        </div>
                        
                        <div class="notification-item">
                            <div class="notification-icon">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div class="notification-content">
                                <h5>Certificate Renewal</h5>
                                <p>Your Food Safety Certification will expire in 30 days. Consider renewing soon.</p>
                                <div class="notification-time">1 day ago</div>
                            </div>
                        </div>
                        
                        <div class="notification-item">
                            <div class="notification-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="notification-content">
                                <h5>Recognition Milestone</h5>
                                <p>You've earned 5 Service Excellence recognitions this year. Great work!</p>
                                <div class="notification-time">3 days ago</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <p>© 2024 HospitalityPro Employee Recognition System. All rights reserved.</p>
        <p>This dashboard is for employee viewing only. All recognitions are system-generated and HR-approved.</p>
    </footer>
    
    <!-- Recognition Details Modal -->
    <div class="modal-overlay" id="recognitionModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Employee of the Month - March 2024</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="recognition-detail-item">
                    <div class="detail-label">Description / Citation</div>
                    <div class="detail-value">Awarded for exceptional customer service, positive guest feedback, and going above and beyond to assist guests during the busy conference season. Your dedication to creating memorable guest experiences has been noted by both management and guests alike.</div>
                </div>
                
                <div class="recognition-detail-item">
                    <div class="detail-label">Recognition Type</div>
                    <div class="detail-value">Award</div>
                </div>
                
                <div class="recognition-detail-item">
                    <div class="detail-label">Issued Date</div>
                    <div class="detail-value">March 15, 2024</div>
                </div>
                
                <div class="recognition-detail-item">
                    <div class="detail-label">Issued By</div>
                    <div class="detail-value">Hotel Management & HR Department</div>
                </div>
                
                <div class="recognition-detail-item">
                    <div class="detail-label">Validity Period</div>
                    <div class="detail-value">Permanent Recognition</div>
                </div>
                
                <div class="recognition-detail-item">
                    <div class="detail-label">Attached Certificate</div>
                    <div class="detail-value">
                        <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                            <i class="fas fa-file-pdf" style="color: #e74c3c; font-size: 1.5rem;"></i>
                            <span>employee_of_the_month_march_2024.pdf</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="modal-btn close-modal-btn" onclick="closeModal()">Close</button>
                <button class="modal-btn download-modal-btn">
                    <i class="fas fa-download"></i> Download Certificate
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functions
        function openModal() {
            document.getElementById('recognitionModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('recognitionModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('recognitionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Notification bell click
        document.querySelector('.notification-bell').addEventListener('click', function() {
            alert('You have 3 new recognition notifications!');
        });
        
        // Logout button
        document.querySelector('.logout-btn').addEventListener('click', function() {
            if (confirm('Are you sure you want to log out?')) {
                alert('Logged out successfully. Redirecting to login page...');
            }
        });
        
        // Simulate loading animation for buttons
        document.querySelectorAll('.action-btn, .certificate-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!this.classList.contains('view-btn')) {
                    e.stopPropagation();
                    
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    this.style.pointerEvents = 'none';
                    
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.style.pointerEvents = 'auto';
                        
                        if (this.classList.contains('download-btn') || this.classList.contains('download-cert-btn')) {
                            alert('Download started! Your certificate will be saved to your device.');
                        } else if (this.classList.contains('share-btn')) {
                            alert('Share link copied to clipboard! You can now share this recognition internally.');
                        }
                    }, 800);
                }
            });
        });
    </script>
      <script src="../../../../soliera.js"></script>
  <script src="../../../../sidebar.js"></script>
</body>
</html>