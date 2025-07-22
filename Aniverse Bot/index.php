<?php
require __DIR__ . '/vendor/autoload.php';

use Telegram\Bot\Api;

$bot = new Api(getenv('TELEGRAM_BOT_TOKEN')); // Replace if hardcoding token

$update = $bot->getWebhookUpdate();
$chatId = $update->getMessage()->getChat()->getId();
$userId = $update->getMessage()->getFrom()->getId();
$text = trim($update->getMessage()->getText());
$adminId = '7751187270'; // Replace this with your Telegram ID

$users = file_exists('users.json') ? json_decode(file_get_contents('users.json'), true) : [];

function saveUsers($users) {
    file_put_contents('users.json', json_encode($users));
}

if ($text === '/start') {
    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => "🎉 *Welcome to Aniflex Anime!*\n\n💰 To access the private channel, please pay ₹50 to:\n\n`8353936723@fam`\n📷 Scan the QR below.\nAfter payment, reply with `/paid`.",
        'parse_mode' => 'Markdown'
    ]);

    $bot->sendPhoto([
        'chat_id' => $chatId,
        'photo' => fopen("upi_qr.png", 'r'),
        'caption' => "📷 Scan to pay ₹50 UPI"
    ]);
} elseif ($text === '/paid') {
    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => "✅ Please send a *screenshot* of your payment.",
        'parse_mode' => 'Markdown'
    ]);
} elseif ($update->getMessage()->getPhoto()) {
    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => "📤 Payment screenshot received. Please wait for confirmation."
    ]);

    $bot->sendMessage([
        'chat_id' => $adminId,
        'text' => "📥 Screenshot received from *User ID:* `$userId`\nReply: `received $userId` to confirm.",
        'parse_mode' => 'Markdown'
    ]);

    $bot->forwardMessage([
        'chat_id' => $adminId,
        'from_chat_id' => $chatId,
        'message_id' => $update->getMessage()->getMessageId()
    ]);
} elseif (strpos($text, 'received ') === 0 && $chatId == $adminId) {
    $parts = explode(' ', $text);
    $targetId = $parts[1] ?? null;

    if ($targetId) {
        $users[$targetId] = true;
        saveUsers($users);

        $bot->sendMessage([
            'chat_id' => $targetId,
            'text' => "🎉 Payment verified!\nHere is your private channel:\n👉 [Join Now](https://t.me/your_channel_link)",
            'parse_mode' => 'Markdown'
        ]);

        $bot->sendMessage([
            'chat_id' => $adminId,
            'text' => "✅ Access granted to user `$targetId`.",
            'parse_mode' => 'Markdown'
        ]);
    }
}
