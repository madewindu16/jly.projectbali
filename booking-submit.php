<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

verify_csrf_token();

$customerName = trim((string) ($_POST['customer_name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$eventDate = trim((string) ($_POST['event_date'] ?? ''));
$eventLocation = trim((string) ($_POST['event_location'] ?? ''));
$note = trim((string) ($_POST['note'] ?? ''));
$cartJson = (string) ($_POST['cart_json'] ?? '[]');
$cartItems = json_decode($cartJson, true);

$date = DateTime::createFromFormat('Y-m-d', $eventDate);
$isValidDate = $date && $date->format('Y-m-d') === $eventDate;

if ($customerName === '' || $phone === '' || !$isValidDate || !is_array($cartItems) || count($cartItems) === 0) {
    header('Location: katalog.php?booking=invalid');
    exit;
}

$date->setTime(0, 0, 0);
if ($date < new DateTime('today')) {
    header('Location: katalog.php?booking=past-date');
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: katalog.php?booking=invalid-email');
    exit;
}

$productIds = [];
foreach ($cartItems as $item) {
    $productId = (int) ($item['id'] ?? 0);
    if ($productId > 0) {
        $productIds[$productId] = true;
    }
}

if (!$productIds) {
    header('Location: katalog.php?booking=empty');
    exit;
}

$ids = array_keys($productIds);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$stmt = $conn->prepare(
    "SELECT p.id, p.name,
            CASE
                WHEN p.discount_price IS NOT NULL AND p.discount_price > 0 THEN p.discount_price
                ELSE p.price
            END AS final_price,
            pb.booked_date
     FROM products p
     LEFT JOIN product_bookings pb ON pb.product_id = p.id AND pb.booked_date = ?
     WHERE p.is_active = 1 AND p.id IN ($placeholders)"
);

$bindValues = array_merge([$eventDate], $ids);
$bindTypes = 's' . $types;
$bindParams = [$bindTypes];
foreach ($bindValues as $index => $value) {
    $bindParams[] = &$bindValues[$index];
}
call_user_func_array([$stmt, 'bind_param'], $bindParams);
$stmt->execute();
$products = $stmt->get_result();

$selectedProducts = [];
$hasBookedConflict = false;
while ($product = $products->fetch_assoc()) {
    if (!empty($product['booked_date'])) {
        $hasBookedConflict = true;
        break;
    }

    $selectedProducts[(int) $product['id']] = $product;
}

if ($hasBookedConflict || count($selectedProducts) !== count($ids)) {
    header('Location: katalog.php?booking=unavailable');
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        'INSERT INTO booking_requests (customer_name, phone, email, event_date, event_location, note)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $emailValue = $email === '' ? null : $email;
    $locationValue = $eventLocation === '' ? null : $eventLocation;
    $noteValue = $note === '' ? null : $note;
    $stmt->bind_param('ssssss', $customerName, $phone, $emailValue, $eventDate, $locationValue, $noteValue);
    $stmt->execute();

    $bookingRequestId = (int) $conn->insert_id;
    $stmt = $conn->prepare(
        'INSERT INTO booking_request_items (booking_request_id, product_id, product_name, price_label)
         VALUES (?, ?, ?, ?)'
    );

    foreach ($selectedProducts as $productId => $product) {
        $productName = $product['name'];
        $priceLabel = 'Rp ' . number_format((float) $product['final_price'], 0, ',', '.') . ' / Hari';
        $stmt->bind_param('iiss', $bookingRequestId, $productId, $productName, $priceLabel);
        $stmt->execute();
    }

    $stmt = $conn->prepare(
        'INSERT IGNORE INTO product_bookings (product_id, booked_date, customer_name, note)
         VALUES (?, ?, ?, ?)'
    );
    $bookingNote = 'Request booking #' . $bookingRequestId;

    foreach (array_keys($selectedProducts) as $productId) {
        $stmt->bind_param('isss', $productId, $eventDate, $customerName, $bookingNote);
        $stmt->execute();
    }

    $conn->commit();
} catch (Throwable $exception) {
    $conn->rollback();
    header('Location: katalog.php?booking=failed');
    exit;
}

header('Location: katalog.php?booking=success');
exit;
