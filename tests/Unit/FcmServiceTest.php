<?php

namespace Tests\Unit;

use App\Services\FcmService;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use PHPUnit\Framework\TestCase;

class FcmServiceTest extends TestCase
{
    public function test_send_to_single_token(): void
    {
        $messaging = $this->createMock(Messaging::class);
        
        $messaging->expects($this->once())
            ->method('send')
            ->with($this->callback(function (CloudMessage $message) {
                $serialized = $message->jsonSerialize();
                return isset($serialized['token']) && $serialized['token'] === 'token-1'
                    && isset($serialized['notification']['title']) && $serialized['notification']['title'] === 'Test Title';
            }));

        $service = new FcmService($messaging);
        $service->sendToTokens(['token-1'], 'Test Title', 'Test Body', ['foo' => 'bar']);
    }

    public function test_send_to_multiple_tokens(): void
    {
        $messaging = $this->createMock(Messaging::class);

        $messaging->expects($this->once())
            ->method('sendMulticast')
            ->with(
                $this->isInstanceOf(CloudMessage::class),
                $this->equalTo(['token-1', 'token-2'])
            )
            ->willReturn(MulticastSendReport::withItems([]));

        $service = new FcmService($messaging);
        $service->sendToTokens(['token-1', 'token-2'], 'Test Title', 'Test Body', ['foo' => 'bar']);
    }

    public function test_does_nothing_when_tokens_are_empty(): void
    {
        $messaging = $this->createMock(Messaging::class);
        $messaging->expects($this->never())->method('send');
        $messaging->expects($this->never())->method('sendMulticast');

        $service = new FcmService($messaging);
        $service->sendToTokens([], 'Test Title', 'Test Body');
    }
}
