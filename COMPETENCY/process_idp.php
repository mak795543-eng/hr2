<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

try {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];

    switch($action) {
        case 'save':
            // Save/Update plan
            $id = $_POST['id'] ?? 0;
            $employee = $_POST['employee'] ?? '';
            $department = $_POST['department'] ?? '';
            $dev_area = $_POST['devArea'] ?? '';
            $training = $_POST['training'] ?? '';
            $timeline = $_POST['timeline'] ?? '';
            $responsible = $_POST['responsible'] ?? '';
            $status = $_POST['status'] ?? '';
            $start_date = $_POST['startDate'] ?? '';
            $end_date = $_POST['endDate'] ?? '';
            $notes = $_POST['notes'] ?? '';

            // Validation
            if (empty($employee) || empty($department) || empty($dev_area) || 
                empty($training) || empty($timeline) || empty($responsible) || 
                empty($status) || empty($start_date) || empty($end_date)) {
                $response['message'] = 'Please fill in all required fields';
                echo json_encode($response);
                exit;
            }

            if ($id == 0) {
                // Insert new plan
                $sql = "INSERT INTO development_plans 
                        (employee, department, dev_area, training, timeline, responsible, status, start_date, end_date, notes) 
                        VALUES (:employee, :department, :dev_area, :training, :timeline, :responsible, :status, :start_date, :end_date, :notes)";
                
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([
                    ':employee' => $employee,
                    ':department' => $department,
                    ':dev_area' => $dev_area,
                    ':training' => $training,
                    ':timeline' => $timeline,
                    ':responsible' => $responsible,
                    ':status' => $status,
                    ':start_date' => $start_date,
                    ':end_date' => $end_date,
                    ':notes' => $notes
                ]);

                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Development plan created successfully!';
                } else {
                    $response['message'] = 'Failed to create development plan';
                }
            } else {
                // Update existing plan
                $sql = "UPDATE development_plans 
                        SET employee = :employee,
                            department = :department,
                            dev_area = :dev_area,
                            training = :training,
                            timeline = :timeline,
                            responsible = :responsible,
                            status = :status,
                            start_date = :start_date,
                            end_date = :end_date,
                            notes = :notes
                        WHERE id = :id";
                
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([
                    ':id' => $id,
                    ':employee' => $employee,
                    ':department' => $department,
                    ':dev_area' => $dev_area,
                    ':training' => $training,
                    ':timeline' => $timeline,
                    ':responsible' => $responsible,
                    ':status' => $status,
                    ':start_date' => $start_date,
                    ':end_date' => $end_date,
                    ':notes' => $notes
                ]);

                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Development plan updated successfully!';
                } else {
                    $response['message'] = 'Failed to update development plan';
                }
            }
            break;

        case 'delete':
            // Delete plan
            $id = $_POST['id'] ?? 0;
            
            if ($id == 0) {
                $response['message'] = 'Invalid plan ID';
                echo json_encode($response);
                exit;
            }

            $sql = "DELETE FROM development_plans WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([':id' => $id]);

            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Development plan deleted successfully!';
            } else {
                $response['message'] = 'Failed to delete development plan';
            }
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }

} catch(PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?>