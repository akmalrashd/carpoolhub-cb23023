<?php

namespace Tests\Feature;

use App\Http\Requests\Push\StorePushSubscriptionRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The push endpoint is client-supplied and the server later POSTs to it, so the
 * allowlist in StorePushSubscriptionRequest is a security boundary, not a
 * cosmetic check. These cases exist so that loosening it — or "simplifying" the
 * rule back to ['required','string'] — fails loudly instead of silently
 * restoring a blind SSRF primitive.
 *
 * No database is touched: the rule is pure validation.
 */
class PushSubscriptionEndpointTest extends TestCase
{
    private function validate(string $endpoint): bool
    {
        return Validator::make(
            [
                'endpoint' => $endpoint,
                'keys' => ['p256dh' => 'test-p256dh-key', 'auth' => 'test-auth-key'],
            ],
            (new StorePushSubscriptionRequest())->rules()
        )->passes();
    }

    /** Endpoints the four real browser push services actually mint. */
    public static function legitimateEndpoints(): array
    {
        return [
            'Chrome FCM' => ['https://fcm.googleapis.com/fcm/send/dGhpcy1pcy1hLXRlc3Q'],
            'Chrome legacy GCM' => ['https://android.googleapis.com/gcm/send/dGVzdA'],
            'Firefox regional' => ['https://updates.push.services.mozilla.com/wpush/v2/gAAAAA'],
            'Edge WNS regional' => ['https://db5p.notify.windows.com/w/?token=BQYAAAB'],
            'Safari' => ['https://web.push.apple.com/QFqQqQqQ'],
        ];
    }

    #[DataProvider('legitimateEndpoints')]
    public function test_it_accepts_real_browser_push_endpoints(string $endpoint): void
    {
        $this->assertTrue(
            $this->validate($endpoint),
            "A legitimate push endpoint was rejected, which would break notifications: {$endpoint}"
        );
    }

    /** Every one of these would make the server dial somewhere it must not. */
    public static function hostileEndpoints(): array
    {
        return [
            'loopback' => ['http://127.0.0.1:3306/'],
            'loopback over https' => ['https://127.0.0.1:6379/'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'private range' => ['https://10.0.0.5/internal'],
            'lan range' => ['https://192.168.1.1/admin'],
            'attacker host' => ['https://evil.example.com/collect'],
            'suffix confusion' => ['https://fcm.googleapis.com.evil.example.com/x'],
            'prefix confusion' => ['https://notfcm.googleapis.com.co/x'],
            'plain http to allowed host' => ['http://fcm.googleapis.com/fcm/send/x'],
            'file scheme' => ['file:///etc/passwd'],
            'gopher scheme' => ['gopher://internal:11211/'],
            'not a url' => ['just-some-text'],
        ];
    }

    #[DataProvider('hostileEndpoints')]
    public function test_it_rejects_endpoints_that_are_not_a_push_service(string $endpoint): void
    {
        $this->assertFalse(
            $this->validate($endpoint),
            "SSRF: this endpoint was accepted and the server would POST to it: {$endpoint}"
        );
    }

    public function test_subdomains_of_allowed_hosts_are_accepted_but_only_as_true_subdomains(): void
    {
        $this->assertTrue($this->validate('https://any.region.notify.windows.com/w/'));
        $this->assertFalse($this->validate('https://notify.windows.com.attacker.test/w/'));
    }
}
