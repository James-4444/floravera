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

// ============================================================
// IMPORTANT: Replace this URL with your Google Colab ngrok URL
// Example: https://xxxx-xx-xx-xxx-xx.ngrok-free.app
// ============================================================
$colab_url = 'https://promotion-eardrum-resigned.ngrok-free.dev';

// Send message to Google Colab backend
$payload = json_encode(['message' => $message]);

$ch = curl_init($colab_url . '/chat');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if($curlError){
    echo json_encode(['reply' => '🌸 Sorry, I am currently offline. Please try again later!']);
    exit();
}

$data = json_decode($response, true);
$reply = $data['reply'] ?? "🌸 I'm sorry, I couldn't process your request. Please try again!";

echo json_encode(['reply' => $reply]);
