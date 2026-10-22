<?php
// Firebase Firestore Configuration
define('FIREBASE_PROJECT_ID', 'your-firebase-project-id'); // <-- Replace with your Firebase Project ID
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024); // 10 MB
define('TEACHER_SIGNUP_CODE', '1234'); // Change this before use

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Helper to convert PHP values to Firestore REST API typed format
function fs_format_value($value) {
    if (is_int($value)) return ['integerValue' => (string)$value];
    if (is_float($value)) return ['doubleValue' => (float)$value];
    if (is_bool($value)) return ['booleanValue' => $value];
    if (is_null($value)) return ['nullValue' => null];
    return ['stringValue' => (string)$value];
}

// Helper to parse Firestore REST API document response back into a standard PHP associative array
function fs_parse_document(array $doc): array {
    $result = [];
    $nameParts = explode('/', $doc['name'] ?? '');
    $result['id'] = end($nameParts);
    
    if (isset($doc['fields'])) {
        foreach ($doc['fields'] as $key => $fieldVal) {
            $type = array_key_first($fieldVal);
            $result[$key] = $fieldVal[$type];
        }
    }
    return $result;
}

// Firestore GET document by ID
function fs_get(string $collection, string $docId): ?array {
    $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/{$collection}/{$docId}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return fs_parse_document($data);
    }
    return null;
}

// Firestore INSERT (Create document with auto-generated ID)
function fs_insert(string $collection, array $data): string {
    $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/{$collection}";
    
    $fields = [];
    foreach ($data as $k => $v) {
        $fields[$k] = fs_format_value($v);
    }
    
    $payload = json_encode(['fields' => $fields]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $resData = json_decode($response, true);
    $nameParts = explode('/', $resData['name'] ?? '');
    return end($nameParts);
}

// Firestore UPDATE document
function fs_update(string $collection, string $docId, array $data): bool {
    $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/{$collection}/{$docId}?";
    $updateMask = [];
    $fields = [];
    
    foreach ($data as $k => $v) {
        $fields[$k] = fs_format_value($v);
        $updateMask[] = "updateMask.fieldPaths=" . $k;
    }
    
    $url .= implode('&', $updateMask);
    $payload = json_encode(['fields' => $fields]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}

// Firestore QUERY collection (Simple equality filter or list all)
function fs_query(string $collection, ?string $field = null, ?string $op = null, $value = null): array {
    $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/{$collection}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return [];

    $data = json_decode($response, true);
    $documents = [];

    if (isset($data['documents'])) {
        foreach ($data['documents'] as $doc) {
            $parsed = fs_parse_document($doc);
            if ($field !== null && isset($parsed[$field])) {
                if ($op === '=' && $parsed[$field] != $value) continue;
            }
            $documents[] = $parsed;
        }
    }
    return $documents;
}

function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function check_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = $_POST['csrf'] ?? '';
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
            http_response_code(419); exit('Your session expired. Please go back, refresh, and try again.');
        }
    }
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): array {
    $user = current_user();
    if (!$user) { header('Location: index.php'); exit; }
    return $user;
}
function require_role(string $role): array {
    $user = require_login();
    if ($user['role'] !== $role) { http_response_code(403); exit('You do not have permission to access this page.'); }
    return $user;
}
function flash(string $message, string $type='success'): void { $_SESSION['flash'] = ['message'=>$message, 'type'=>$type]; }
function take_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
