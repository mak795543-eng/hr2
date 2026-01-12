<?php
require_once 'config/db.php';

$db = new Database();
$conn = $db->connect();

try {
    // Create table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS development_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee VARCHAR(255) NOT NULL,
        department VARCHAR(255) NOT NULL,
        dev_area VARCHAR(255) NOT NULL,
        training VARCHAR(255) NOT NULL,
        timeline VARCHAR(100) NOT NULL,
        responsible VARCHAR(255) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'Not Started',
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_department (department),
        INDEX idx_status (status),
        INDEX idx_employee (employee)
    )";

    $conn->exec($sql);
    
    // Check if table has data
    $check = $conn->query("SELECT COUNT(*) as count FROM development_plans");
    $result = $check->fetch();
    
    if ($result['count'] == 0) {
        // Insert sample data
        $sample_data = [
            [
                'employee' => 'John Smith',
                'department' => 'Financial Accounting',
                'dev_area' => 'Advanced Excel Skills',
                'training' => 'Advanced Excel Workshop',
                'timeline' => 'Q1 2024',
                'responsible' => 'Mary Johnson',
                'status' => 'In Progress',
                'start_date' => '2024-01-15',
                'end_date' => '2024-03-15',
                'notes' => 'Focus on pivot tables and advanced formulas'
            ],
            [
                'employee' => 'Sarah Chen',
                'department' => 'HR Management',
                'dev_area' => 'Leadership Development',
                'training' => 'Leadership Training Program',
                'timeline' => 'Q2 2024',
                'responsible' => 'Robert Williams',
                'status' => 'Not Started',
                'start_date' => '2024-04-01',
                'end_date' => '2024-06-30',
                'notes' => 'Complete all leadership modules'
            ],
            [
                'employee' => 'Michael Brown',
                'department' => 'Hotel Operations',
                'dev_area' => 'Customer Service Excellence',
                'training' => 'Hospitality Management Course',
                'timeline' => 'Q3 2024',
                'responsible' => 'Lisa Anderson',
                'status' => 'Completed',
                'start_date' => '2023-07-01',
                'end_date' => '2023-09-30',
                'notes' => 'Successfully completed all requirements'
            ],
            [
                'employee' => 'Emily Davis',
                'department' => 'Restaurant Management',
                'dev_area' => 'Menu Planning & Costing',
                'training' => 'Culinary Management Training',
                'timeline' => 'Q4 2024',
                'responsible' => 'David Wilson',
                'status' => 'On Hold',
                'start_date' => '2024-10-01',
                'end_date' => '2024-12-31',
                'notes' => 'Awaiting budget approval'
            ],
            [
                'employee' => 'James Wilson',
                'department' => 'Financial Accounting',
                'dev_area' => 'Financial Reporting',
                'training' => 'IFRS Certification',
                'timeline' => 'Q1 2024',
                'responsible' => 'Mary Johnson',
                'status' => 'In Progress',
                'start_date' => '2024-02-01',
                'end_date' => '2024-04-30',
                'notes' => 'Working on module 3 of 5'
            ],
            [
                'employee' => 'Jessica Lee',
                'department' => 'HR Management',
                'dev_area' => 'Talent Acquisition',
                'training' => 'Recruitment Specialist Course',
                'timeline' => 'Q2 2024',
                'responsible' => 'Robert Williams',
                'status' => 'Not Started',
                'start_date' => '2024-05-15',
                'end_date' => '2024-08-15',
                'notes' => 'To be scheduled'
            ]
        ];

        $insert_sql = "INSERT INTO development_plans 
            (employee, department, dev_area, training, timeline, responsible, status, start_date, end_date, notes) 
            VALUES (:employee, :department, :dev_area, :training, :timeline, :responsible, :status, :start_date, :end_date, :notes)";

        $stmt = $conn->prepare($insert_sql);

        foreach ($sample_data as $data) {
            $stmt->execute($data);
        }
        
        echo json_encode(['success' => true, 'message' => 'Database table created and sample data added successfully.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Database table already exists with data.']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>