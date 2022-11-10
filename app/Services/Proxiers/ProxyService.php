<?php

namespace App\Services\Proxiers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxyService
{
    private function getPpid()
    {
        $username = config('setting.credentials.user_id');
        $password = config('setting.credentials.passid');

        return $username . '|' . $password;
    }

    public function inquiry($uuid, $productCode, $customerCode, $amount = '')
    {
        $dTime = now()->setTimezone('Asia/Jakarta')->toDateTimeString();
        $url = config('setting.url.inquiry');
        $ppidRaw = $this->getPpid();
        $ppid = base64_encode($ppidRaw);
        $udataRaw = $productCode . '|' . $customerCode;
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

        Log::info('Inquiry', [
            'payload' => $payload,
        ]);

        $response = Http::withoutVerifying()
            ->post($url, $payload);

        return $response->json();
    }

    public function pay($uuid, $productCode, $customerCode, $respid, $amount = '')
    {
        $dTime = now()->setTimezone('Asia/Jakarta')->toDateTimeString();
        $url = config('setting.url.payment');
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

        Log::info('Pay', [
            'payload' => $payload,
        ]);
        $response = Http::withoutVerifying()
            ->post($url, $payload);

        return $response->json();
    }

    public function payWithYearAndMonth()
    {
        return [];
    }

    public function purchase($uuid, $productCode, $customerCode, $amount = '')
    {
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

        Log::info('Purchase', [
            'payload' => $payload,
        ]);
        $response = Http::withoutVerifying()
            ->post($url, $payload);

        return $response->json();
    }
}
