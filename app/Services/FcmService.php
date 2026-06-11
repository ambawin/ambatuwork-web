<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;

class FcmService
{
    public function __construct(private readonly Messaging $messaging)
    {
    }

    /**
     * Send a push notification to a list of FCM device tokens.
     *
     * @param  string[]  $tokens     FCM registration tokens to target
     * @param  string    $title      Notification title
     * @param  string    $body       Notification body
     * @param  array<string, string> $data  Optional key-value data payload
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        $notification = Notification::create($title, $body);

        // Cast all data values to string — FCM requires string-only data payloads
        $stringData = array_map('strval', $data);

        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($stringData);

        try {
            if (count($tokens) === 1) {
                $this->messaging->send(
                    $message->withToken($tokens[0])
                );
            } else {
                $this->messaging->sendMulticast($message, $tokens);
            }
        } catch (MessagingException $e) {
            Log::error('FCM send failed', [
                'error' => $e->getMessage(),
                'token_count' => count($tokens),
            ]);
        }
    }
}
