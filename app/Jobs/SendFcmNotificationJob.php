<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int[]
     */
    public array $backoff = [10, 30, 60];

    /**
     * @param  int                    $userId  Target user ID
     * @param  string                 $title   Notification title
     * @param  string                 $body    Notification body text
     * @param  array<string, string>  $data    Optional FCM data payload
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(FcmService $fcmService): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            return;
        }

        $tokens = $user->deviceTokens()->pluck('fcm_token')->all();

        if (empty($tokens)) {
            return;
        }

        $fcmService->sendToTokens($tokens, $this->title, $this->body, $this->data);
    }
}
