<?php
session_start();
include 'dbconnect.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['reply' => 'Please log in to use the chatbot.']);
    exit();
}

$uid = (int)$_SESSION['user_id'];
$message = trim($_POST['message'] ?? '');

if(empty($message)){
    echo json_encode(['reply' => 'Please type a message.']);
    exit();
}

// Fetch user's recent orders for context
$ordersContext = '';
$ordersResult = $conn->query("SELECT o.id, o.status, o.total, o.created_at,
    GROUP_CONCAT(p.name SEPARATOR ', ') AS items
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN products p ON p.id = oi.product_id
    WHERE o.customer_id = $uid
    GROUP BY o.id ORDER BY o.created_at DESC LIMIT 5");

if($ordersResult && $ordersResult->num_rows > 0){
    $ordersContext = "The customer's recent orders are:\n";
    while($o = $ordersResult->fetch_assoc()){
        $ordersContext .= "- Order #".$o['id'].": ".$o['items']." | Status: ".$o['status']." | Total: ₱".$o['total']." | Date: ".$o['created_at']."\n";
    }
} else {
    $ordersContext = "The customer has no orders yet.";
}

// Fetch top selling / available products
$productsContext = '';
$productsResult = $conn->query("SELECT p.name, p.category, p.price, p.emoji, v.shop_name
    FROM products p
    JOIN vendors v ON v.id = p.vendor_id
    ORDER BY p.id DESC LIMIT 10");

if($productsResult && $productsResult->num_rows > 0){
    $productsContext = "Available products on Floravera:\n";
    while($p = $productsResult->fetch_assoc()){
        $productsContext .= "- ".$p['emoji']." ".$p['name']." (".$p['category'].") - ₱".$p['price']." from ".$p['shop_name']."\n";
    }
}

// Check if user is already a vendor
$vendorRow = $conn->query("SELECT status FROM vendors WHERE user_id=$uid LIMIT 1")->fetch_assoc();
$vendorStatus = $vendorRow ? $vendorRow['status'] : 'not_a_vendor';

// Build system prompt
$systemPrompt = "You are Flora, a friendly and helpful chatbot assistant for Floravera — Davao City's premier online marketplace for local florists and handicraft makers.

Your job is to assist customers with their questions about:
1. Their orders and delivery status
2. Available flowers, bouquets, and handicraft products
3. How to become a vendor on Floravera
4. General FAQs about the platform

Here is the context you need to answer the customer:

CUSTOMER ORDER INFORMATION:
$ordersContext

AVAILABLE PRODUCTS:
$productsContext

VENDOR STATUS OF THIS CUSTOMER: $vendorStatus

HOW TO BECOME A VENDOR on Floravera:
- The customer must be logged in and go to their dashboard
- Click 'Become a Vendor' in the sidebar under MY SHOP section
- Fill out the vendor application form with their shop name and details
- Pay the one-time registration fee
- Wait for admin approval
- Once approved, they can start uploading products and managing their shop

DELIVERY INFORMATION:
- Delivery is handled by individual vendors/sellers
- Customers should check their order status in the My Orders section
- For delivery concerns, customers can contact the vendor directly through the platform

PLATFORM INFORMATION:
- Floravera is a web-based marketplace for local florists and handicraft makers in Davao City
- Customers can browse flowers, bouquets, handicrafts, and gift sets
- Orders can be paid via cash or cash on delivery

IMPORTANT RULES:
- Always be friendly, warm, and helpful
- Keep responses short and concise — maximum 3 sentences
- If you don't know the answer, politely say you will forward the concern to the admin
- Always refer to yourself as Flora
- Use Filipino-friendly English (simple and easy to understand)
- Add a relevant flower emoji occasionally to keep it fun 🌸";

// Call Google Gemini API
$apiKey = 'AIzaSyDayoQo2mVY2Jmaszxh-NyfSDta-PeJEDo'; // <-- Replace with your actual Gemini API key

$fullPrompt = $systemPrompt . "\n\nCustomer message: " . $message;

$payload = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => $fullPrompt]
            ]
        ]
    ],
    'generationConfig' => [
        'maxOutputTokens' => 300,
        'temperature' => 0.7
    ]
]);

$ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for XAMPP SSL issue
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Fix for XAMPP SSL issue
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

// Debug: log response to check for errors
if($curlError){
    echo json_encode(['reply' => "Connection error: " . $curlError . " 🌸"]);
    exit();
}

$data = json_decode($response, true);

// Check for API error response
if(isset($data['error'])){
    $errMsg = $data['error']['message'] ?? 'Unknown API error';
    echo json_encode(['reply' => "API Error: " . $errMsg]);
    exit();
}

$reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? "I'm sorry, I couldn't process your request. Please try again! 🌸";

echo json_encode(['reply' => $reply]);
