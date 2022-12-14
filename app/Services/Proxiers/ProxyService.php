<?php

namespace App\Services\Proxiers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxyService
{
    private $isCheckStatus = false;

    private function getPpid()
    {
        $username = config('setting.credentials.user_id');
        $password = config('setting.credentials.passid');

        return $username . '|' . $password;
    }

    public function inquiry($trxid, $productCode, $customerCode, $options = [])
    {
        $amount = $options['amount'] ?? '';
        $isPbb = $options['is_pbb'] ?? false;

        $transaction = Transaction::where('trxid', $trxid)->first();
        if (!blank($transaction)) {
            return $this->checkStatus($trxid);
        }
        $dateTime = now()->setTimezone('Asia/Jakarta');

        $uuid = config('setting.credentials.partner_id');
        $dTime = $dateTime->toDateTimeString();
        $url = $isPbb ? config('setting.url.pbb_inquiry') : config('setting.url.inquiry');
        $ppidRaw = $this->getPpid();
        $ppid = base64_encode($ppidRaw);
        $udataRaw = $productCode . '|' . $customerCode . '|' . $amount;
        if (blank($amount)) {
            $udataRaw = $productCode . '|' . $customerCode;
        }
        $udata = base64_encode($udataRaw);
        $ket = '';
        $xApiKey = config('setting.credentials.x_api_key');
        $signature = md5("tele-android-" . $uuid . $ket . $dTime . $ppidRaw . $udataRaw . $xApiKey . "-indonesia");
        $payload = [
            'uuid' => $uuid,
            'ppid' => $ppid,
            'udata' => $udata,
            'dtime' => $dTime,
            'ket' => $ket,
            'X-API-KEY' => $xApiKey,
            'signature' => $signature,
        ];

        Log::info('Before Inquiry', [
            'payload' => $payload,
            'udataRaw'=> $udataRaw,
            'datetime' => now()->toDateTimeString(),
            'url' => $url,
            'trxid' => $trxid
        ]);

        $response = Http::withoutVerifying()
            ->post($url, $payload);

        Log::info('After Inquiry ', [
            'datetime' => now()->toDateTimeString(),
            'url' => $url,
            'response_http_code'=> $response->status(),
            'response_reason' => $response->reason(),
            'response_content' => $response->body(),
            'response_json' => $response->json() ?? ''
        ]);
        $responseData = $response->json();

        $respid = $responseData['respid'] ?? null;

        if(blank($respid) || is_null($respid)) {
            Log::alert('inquiry respid null ', [
                'response' => $responseData
            ]);
            throw new \Exception($responseData['response'] ?? 'Tidak dapat melakukan inquiry.');
        }

        $transaction = Transaction::create([
                'trxid' => $trxid,
                'date' => $dateTime->toDateString(),
                'product_code' => $productCode,
                'customer_code' => $customerCode,
                'respid' => $respid,
            ]
        );
        return $responseData;
    }

//    public function pay($trxid, $productCode, $customerCode, $respid, $options = [])

    /**
     * @throws \Exception
     */
    public function pay($trxid, $productCode, $customerCode, $options = [])
    {
        $amount = $options['amount'] ?? '';
        $isPbb = $options['is_pbb'] ?? false;

        $transaction = Transaction::where('trxid', $trxid)->first();

        if (!blank($transaction)) {
            return $this->checkStatus($trxid);
        } else {
            $responseData = $this->inquiry($trxid, $productCode, $customerCode, $options);
            $transaction = Transaction::where('trxid', $trxid)->first();
        }

        $respid = $transaction->respid;

        if (blank($respid)) {
            Log::info('Transaction data: ', [
                'transaction' => $transaction,
                'datetime' => now()->toDateTimeString()
            ]);
            throw new \Exception('respid salah.');
        }

        $dateTime = now()->setTimezone('Asia/Jakarta');
        $uuid = config('setting.credentials.partner_id');
        $dTime = now()->setTimezone('Asia/Jakarta')->toDateTimeString();
        $url = $isPbb ? config('setting.url.pbb_payment') : config('setting.url.payment');
        $ppidRaw = $this->getPpid();
        $ppid = base64_encode($ppidRaw);
        $udataRaw = $productCode . '|' . $customerCode . '|' . $respid . '|' . $amount;

        if (blank($amount)) {
            $udataRaw = $productCode . '|' . $customerCode . '|' . $respid;
        }

        $udata = base64_encode($udataRaw);
        $ket = '';
        $xApiKey = config('setting.credentials.x_api_key');
        $signature = md5("tele-android-" . $uuid . $ket . $dTime . $ppidRaw . $udataRaw . $xApiKey . "-indonesia");
        $payload = [
            'uuid' => $uuid,
            'ppid' => $ppid,
            'udata' => $udata,
            'dtime' => $dTime,
            'ket' => $ket,
            'X-API-KEY' => $xApiKey,
            'signature' => $signature,
        ];

        Log::info('Before Pay', [
            'payload' => $payload,
            'udataRaw'=> $udataRaw,
            'datetime' => now()->toDateTimeString(),
            'url' => $url,
            'trxid' => $trxid
        ]);

        $response = Http::withoutVerifying()
            ->post($url, $payload);

        Log::info('After Pay', [
            'datetime' => now()->toDateTimeString(),
            'url' => $url,
            'response_http_code'=> $response->status(),
            'response_reason' => $response->reason(),
            'response_content' => $response->body(),
            'response_json' => $response->json() ?? ''
        ]);
        return $response->json();
    }

    public function purchase($trxid, $productCode, $customerCode, $amount = '')
    {
        $transaction = Transaction::where('trxid', $trxid)->first();
        if (!blank($transaction)) {
            return $this->checkStatus($trxid);
        }

        $dateTime = now()->setTimezone('Asia/Jakarta');

        $uuid = config('setting.credentials.partner_id');
        $dTime = now()->setTimezone('Asia/Jakarta')->toDateTimeString();
        $url = config('setting.url.purchase');
        $ppidRaw = $this->getPpid();
        $ppid = base64_encode($ppidRaw);
        $udataRaw = $productCode . '|' . $customerCode;
        $udata = base64_encode($udataRaw);
        $xApiKey = config('setting.credentials.x_api_key');
        $signature = md5("tele-android-" . $uuid . $dTime . $ppidRaw . $udataRaw . $xApiKey . "-indonesia");
        $payload = [
            'uuid' => $uuid,
            'ppid' => $ppid,
            'udata' => $udata,
            'dtime' => $dTime,
            'X-API-KEY' => $xApiKey,
            'signature' => $signature,
        ];

        Log::info('Before Purchase', [
            'payload' => $payload,
            'datetime' => now()->toDateTimeString(),
            'url' => $url,
            'trxid' => $trxid
        ]);

        $response = Http::withoutVerifying()
            ->post($url, $payload);

        Log::info('After Purchase ', [
            'datetime' => now()->toDateTimeString(),
            'url' => $url,
            'response_http_code'=> $response->status(),
            'response_reason' => $response->reason(),
            'response_content' => $response->body(),
            'response_json' => $response->json() ?? ''
        ]);
        $responseData = $response->json();

        if (!$this->isCheckStatus) {
            $transaction = Transaction::create([
                    'trxid' => $trxid,
                    'date' => $dateTime->toDateString(),
                    'product_code' => $productCode,
                    'customer_code' => $customerCode,
                    'respid' => $responseData['respid'] ?? null
                ]
            );
        }

        return $responseData;
    }

    public function checkStatus($trxid)
    {
        $dateTime = now()->setTimezone('Asia/Jakarta');
        $transaction = Transaction::where('trxid', $trxid)->first();
        if (blank($transaction)) {
            return ['status' => false, 'response' => 'trxid belum pernah digunakan.'];
        }
        $productCode = $transaction->product_code;
        $customerCode = $transaction->customer_code;

        $uuid = config('setting.credentials.partner_id');
        $dTime = $dateTime->toDateTimeString();
        $url = config('setting.url.status');
        $ppidRaw = $this->getPpid();
        $ppid = base64_encode($ppidRaw);
        $udataRaw = $productCode . '|' . $customerCode . '|' . ($transaction->date ?? $dateTime->toDateString());
        $udata = base64_encode($udataRaw);
        $xApiKey = config('setting.credentials.x_api_key');
        $signature = md5("tele-android-" . $uuid . $dTime . $ppidRaw . $udataRaw . $xApiKey . "-indonesia");
        $payload = [
            'uuid' => $uuid,
            'ppid' => $ppid,
            'udata' => $udata,
            'dtime' => $dTime,
            'X-API-KEY' => $xApiKey,
            'signature' => $signature,
        ];

        Log::info('Before Cek status ', [
            'payload' => $payload,
            'datetime' => now()->toDateTimeString(),
            'url' => $url,
            'trxid' => $trxid
        ]);

        $response = Http::withoutVerifying()->post($url, $payload);

        Log::info('After Cek status ', [
            'datetime' => now()->toDateTimeString(),
            'url' => $url,
            'response_http_code'=> $response->status(),
            'response_reason' => $response->reason(),
            'response_content' => $response->body(),
            'response_json' => $response->json() ?? ''
        ]);
        return $response->json();
    }
}
