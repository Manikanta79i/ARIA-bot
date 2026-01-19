<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Read webhook
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Log every hit
file_put_contents(__DIR__."/hit.log", date("H:i:s")." HIT\n", FILE_APPEND);

// Validate message
if (
    empty($data['event']) ||
    $data['event'] !== 'message' ||
    empty($data['payload']['body']) ||
    !empty($data['payload']['fromMe'])
) {
    exit("IGNORED");
}

$chatId = $data['payload']['from'];
$userMessage = trim($data['payload']['body']);

// ---- MEMORY ----
$memoryFile = __DIR__ . "/memory/" . md5($chatId) . ".json";
$history = file_exists($memoryFile)
    ? json_decode(file_get_contents($memoryFile), true)
    : [];

// Save user message
$history[] = ["role" => "user", "content" => $userMessage];
$history = array_slice($history, -10);

// ---- AI CALL ----
$aiReply = getAIReply($history);

// Save bot reply
$history[] = ["role" => "assistant", "content" => $aiReply];
file_put_contents($memoryFile, json_encode($history));

// ---- SEND REPLY ----
$send = [
    "session" => "default",
    "chatId" => $chatId,
    "text" => $aiReply
];

$ch = curl_init("http://localhost:3000/api/sendText");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
       "X-API-Key: ".getenv("WAHA_API_KEY")
    ],
    CURLOPT_POSTFIELDS => json_encode($send)
]);

curl_exec($ch);
curl_close($ch);

echo "OK";


// -------- AI FUNCTION --------
function getAIReply($history)
{
   $apiKey = getenv("GROQ_API_KEY");


    $messages = array_merge(
        [[
            "role" => "system",
            "content" => "You are Maniu, a friendly human-like WhatsApp assistant. Reply naturally and remember context."
        ]],
        $history
    );

    $payload = [
        "model" => "llama-3.3-70b-versatile",
        "messages" => $messages,
        "temperature" => 0.6
    ];

    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer ".$apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);

    return $json['choices'][0]['message']['content']
        ?? "Hmm… can you explain a bit more?";
}

