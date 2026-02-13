<?php
require 'auth.php';
require 'db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'create':
            $response = createRecord($pdo);
            break;
        case 'read':
            $response = readRecord($pdo, $_POST['id'] ?? 0);
            break;
        case 'update':
            $response = updateRecord($pdo);
            break;
        case 'update_by_epic':
            $response = updateRecordByEpic($pdo);
            break;
        case 'delete':
            $response = deleteRecord($pdo, $_POST['id'] ?? 0);
            break;
        default:
            $response = ['success' => false, 'message' => 'Unknown action: ' . $action];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
}

echo json_encode($response);

// ============================================================
// UPDATE - Update existing record by EPIC
// ============================================================
function updateRecordByEpic($pdo) {
    $epic = $_POST['epic'] ?? '';
    if (!$epic) {
        return ['success' => false, 'message' => 'EPIC is required'];
    }
    
    $sql = "UPDATE otn_2026 SET 
            party = :party, polling_status = :polling_status, government_beneficiaries = :gov_ben, postal = :postal, mobile = :mobile
            WHERE epic = :epic";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':epic' => $epic,
        ':party' => $_POST['party'] ?: null,
        ':polling_status' => $_POST['polling_status'] ?: null,
        ':gov_ben' => $_POST['government_beneficiaries'] ?: null,
        ':postal' => $_POST['postal'] ?: null,
        ':mobile' => $_POST['mobile'] ?: null
    ]);
    
    return ['success' => true, 'message' => 'Record Added Successfully'];
}

// ============================================================
// CREATE - Insert new record
// ============================================================
function createRecord($pdo) {
    $required = ['sno', 'epic', 'name_ta', 'name_en', 'rel_type', 'rel_name_ta', 'rel_name_en', 'house_no', 'age', 'gender', 'booth', 'page'];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            return ['success' => false, 'message' => "Missing required field: $field"];
        }
    }
    
    // Check if EPIC already exists
    $checkStmt = $pdo->prepare("SELECT id FROM otn_2026 WHERE epic = :epic");
    $checkStmt->execute([':epic' => $_POST['epic']]);
    if ($checkStmt->fetch()) {
        return ['success' => false, 'message' => 'EPIC number already exists'];
    }
    
    $sql = "INSERT INTO otn_2026 (sno, epic, name_ta, name_en, rel_type, rel_name_ta, rel_name_en, house_no, age, gender, booth, page, mobile, party, polling_status, government_beneficiaries, postal)
            VALUES (:sno, :epic, :name_ta, :name_en, :rel_type, :rel_name_ta, :rel_name_en, :house_no, :age, :gender, :booth, :page, :mobile, :party, :polling_status, :gov_ben, :postal)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':sno' => $_POST['sno'],
        ':epic' => $_POST['epic'],
        ':name_ta' => $_POST['name_ta'],
        ':name_en' => $_POST['name_en'],
        ':rel_type' => $_POST['rel_type'],
        ':rel_name_ta' => $_POST['rel_name_ta'],
        ':rel_name_en' => $_POST['rel_name_en'],
        ':house_no' => $_POST['house_no'],
        ':age' => (int)$_POST['age'],
        ':gender' => $_POST['gender'],
        ':booth' => $_POST['booth'],
        ':page' => $_POST['page'],
        ':mobile' => $_POST['mobile'] ?: null,
        ':party' => $_POST['party'] ?: null,
        ':polling_status' => $_POST['polling_status'] ?: null,
        ':gov_ben' => $_POST['government_beneficiaries'] ?: null,
        ':postal' => $_POST['postal'] ?: null
    ]);
    
    return ['success' => true, 'message' => 'Record created successfully', 'id' => $pdo->lastInsertId()];
}

// ============================================================
// READ - Get single record by ID
// ============================================================
function readRecord($pdo, $id) {
    if (!$id) {
        return ['success' => false, 'message' => 'ID is required'];
    }
    
    $stmt = $pdo->prepare("SELECT * FROM otn_2026 WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch();
    
    if ($record) {
        return ['success' => true, 'data' => $record];
    }
    return ['success' => false, 'message' => 'Record not found'];
}

// ============================================================
// UPDATE - Update existing record
// ============================================================
function updateRecord($pdo) {
    $id = $_POST['id'] ?? 0;
    if (!$id) {
        return ['success' => false, 'message' => 'ID is required for update'];
    }
    
    $required = ['sno', 'epic', 'name_ta', 'name_en', 'rel_type', 'rel_name_ta', 'rel_name_en', 'house_no', 'age', 'gender', 'booth', 'page'];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            return ['success' => false, 'message' => "Missing required field: $field"];
        }
    }
    
    // Check if EPIC already exists for another record
    $checkStmt = $pdo->prepare("SELECT id FROM otn_2026 WHERE epic = :epic AND id != :id");
    $checkStmt->execute([':epic' => $_POST['epic'], ':id' => $id]);
    if ($checkStmt->fetch()) {
        return ['success' => false, 'message' => 'EPIC number already exists for another voter'];
    }
    
    $sql = "UPDATE otn_2026 SET 
            sno = :sno, epic = :epic, name_ta = :name_ta, name_en = :name_en,
            rel_type = :rel_type, rel_name_ta = :rel_name_ta, rel_name_en = :rel_name_en,
            house_no = :house_no, age = :age, gender = :gender, booth = :booth, page = :page,
            mobile = :mobile, party = :party, polling_status = :polling_status, government_beneficiaries = :gov_ben, postal = :postal
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':sno' => $_POST['sno'],
        ':epic' => $_POST['epic'],
        ':name_ta' => $_POST['name_ta'],
        ':name_en' => $_POST['name_en'],
        ':rel_type' => $_POST['rel_type'],
        ':rel_name_ta' => $_POST['rel_name_ta'],
        ':rel_name_en' => $_POST['rel_name_en'],
        ':house_no' => $_POST['house_no'],
        ':age' => (int)$_POST['age'],
        ':gender' => $_POST['gender'],
        ':booth' => $_POST['booth'],
        ':page' => $_POST['page'],
        ':mobile' => $_POST['mobile'] ?: null,
        ':party' => $_POST['party'] ?: null,
        ':polling_status' => $_POST['polling_status'] ?: null,
        ':gov_ben' => $_POST['government_beneficiaries'] ?: null,
        ':postal' => $_POST['postal'] ?: null
    ]);
    
    return ['success' => true, 'message' => 'Record updated successfully'];
}

// ============================================================
// DELETE - Remove record by ID
// ============================================================
function deleteRecord($pdo, $id) {
    if (!$id) {
        return ['success' => false, 'message' => 'ID is required for delete'];
    }
    
    $stmt = $pdo->prepare("DELETE FROM otn_2026 WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    if ($stmt->rowCount() > 0) {
        return ['success' => true, 'message' => 'Record deleted successfully'];
    }
    return ['success' => false, 'message' => 'Record not found or already deleted'];
}
?>
