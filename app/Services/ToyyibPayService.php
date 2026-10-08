<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * A thin wrapper around ToyyibPay's REST API. There is no business logic in
 * here, since that belongs to GatewayPaymentService.
 *
 * It follows the same shape as TelegramService. Each call builds its own
 * Guzzle client, is wrapped in a try/catch, and on failure logs the error and
 * returns null instead of throwing at whoever called it.
 *
 * Two casing/units gotchas confirmed against ToyyibPay's own docs/examples,
 * easy to get wrong:
 * - The same identifier is spelled 3 different ways depending on direction:
 *   `BillCode` (PascalCase, in the createBill response), `billCode`
 *   (camelCase, in the getBillTransactions request), `billcode` (all
 *   lowercase, in the Return/Callback query string).
 * - `billAmount` sent to createBill is in CENTS ("100" = RM1.00), but
 *   `billpaymentAmount` returned by getBillTransactions is a plain RM
 *   decimal string ("10.00"), so the two are not in the same unit.
 */
class ToyyibPayService
{
    private function client(): Client
    {
        return new Client([
            'base_uri' => rtrim((string) config('services.toyyibpay.base_url'), '/').'/',
            'timeout' => 15,
        ]);
    }

    private function secretKey(): string
    {
        return (string) config('services.toyyibpay.secret_key');
    }

    public function isConfigured(): bool
    {
        return ! empty(config('services.toyyibpay.secret_key')) && ! empty(config('services.toyyibpay.category_code'));
    }

    public function createCategory(string $name, string $description): ?string
    {
        try {
            $response = $this->client()->post('index.php/api/createCategory', [
                'form_params' => [
                    'catname' => $this->sanitizeText($name, 30),
                    'catdescription' => $this->sanitizeText($description, 100),
                    'userSecretKey' => $this->secretKey(),
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return $body[0]['CategoryCode'] ?? null;
        } catch (GuzzleException $e) {
            Log::error('ToyyibPay createCategory failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @param  array{amount_rm: float, bill_name: string, bill_description: string, external_reference_no: string, return_url: string, callback_url: string, payer_name: string, payer_email: string, payer_phone: string}  $params
     */
    public function createBill(array $params): ?string
    {
        try {
            $response = $this->client()->post('index.php/api/createBill', [
                'form_params' => [
                    'userSecretKey' => $this->secretKey(),
                    'categoryCode' => (string) config('services.toyyibpay.category_code'),
                    'billName' => $this->sanitizeText($params['bill_name'], 30),
                    'billDescription' => $this->sanitizeText($params['bill_description'], 100),
                    'billPriceSetting' => 1,
                    'billPayorInfo' => 1,
                    'billAmount' => (string) (int) round($params['amount_rm'] * 100),
                    'billReturnUrl' => $params['return_url'],
                    'billCallbackUrl' => $params['callback_url'],
                    'billExternalReferenceNo' => $params['external_reference_no'],
                    'billTo' => $params['payer_name'],
                    'billEmail' => $params['payer_email'],
                    'billPhone' => $params['payer_phone'],
                    'billSplitPayment' => 0,
                    // FPX only. Card payments are left out because their fees
                    // are percentage based and noticeably higher, which the
                    // flat fee formula in GatewayPaymentService does not cover.
                    'billPaymentChannel' => 0,
                    'billExpiryDays' => 1,
                    'enableDuitNowQR' => 1,
                    // Required whenever enableDuitNowQR=1, or ToyyibPay rejects
                    // the whole bill. 0 = fee deducted from our own settlement,
                    // matching what FPX does by default. The rule that the
                    // passenger covers the fee is already built into billAmount
                    // ourselves, so both channels end up charged the same way.
                    'chargeDuitNowQR' => 0,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return $body[0]['BillCode'] ?? null;
        } catch (GuzzleException $e) {
            Log::error('ToyyibPay createBill failed: '.$e->getMessage());

            return null;
        }
    }

    public function getBillTransactions(string $billCode): ?array
    {
        try {
            $response = $this->client()->post('index.php/api/getBillTransactions', [
                'form_params' => [
                    'billCode' => $billCode,
                    'userSecretKey' => $this->secretKey(),
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return $body[0] ?? null;
        } catch (GuzzleException $e) {
            Log::error('ToyyibPay getBillTransactions failed: '.$e->getMessage(), ['bill_code' => $billCode]);

            return null;
        }
    }

    public function checkoutUrl(string $billCode): string
    {
        return rtrim((string) config('services.toyyibpay.base_url'), '/').'/'.$billCode;
    }

    /**
     * ToyyibPay's callback hash formula: MD5(secretKey + status + order_id +
     * refno + "ok"). The values must be used exactly as they arrived, with no
     * casting to int first, because ToyyibPay built the hash from the literal
     * strings it posted.
     */
    public function verifyCallbackHash(array $payload): bool
    {
        $expected = md5(
            $this->secretKey().
            (string) ($payload['status'] ?? '').
            (string) ($payload['order_id'] ?? '').
            (string) ($payload['refno'] ?? '').
            'ok'
        );

        return hash_equals($expected, (string) ($payload['hash'] ?? ''));
    }

    /**
     * ToyyibPay rejects billName/billDescription containing anything but
     * alphanumeric characters, spaces and underscores.
     */
    private function sanitizeText(string $text, int $maxLength): string
    {
        $clean = preg_replace('/[^A-Za-z0-9 _]/', '', $text) ?? '';

        return trim(mb_substr($clean, 0, $maxLength));
    }
}
