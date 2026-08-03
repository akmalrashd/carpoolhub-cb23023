<?php

namespace App\Http\Requests\Push;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The push endpoint is chosen by the client, and PushService later makes the
 * server POST to it. Validating it as a bare string therefore handed any
 * logged-in account a blind SSRF primitive: point the endpoint at
 * http://127.0.0.1:3306 or a cloud metadata address and the server dials it.
 *
 * Real browsers only ever mint endpoints on the five hosts in
 * config('app.push_endpoint_hosts'), so constraining to those is invisible to
 * legitimate clients while closing the hole.
 */
class StorePushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'max:2048', $this->pushEndpointRule()],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Accept only https:// URLs whose host is, or is a subdomain of, a known
     * browser push service. Firefox and WNS both use regional subdomains
     * (updates.push.services.mozilla.com, db5p.notify.windows.com), so a
     * suffix match is required rather than an exact host match.
     */
    protected function pushEndpointRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $url = parse_url((string) $value);

            if ($url === false || ($url['scheme'] ?? '') !== 'https' || empty($url['host'])) {
                $fail('The push endpoint must be a https URL.');

                return;
            }

            $host = strtolower($url['host']);

            foreach ((array) config('app.push_endpoint_hosts', []) as $allowed) {
                $allowed = strtolower((string) $allowed);

                if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                    return;
                }
            }

            $fail('The push endpoint is not a recognised push service.');
        };
    }
}
