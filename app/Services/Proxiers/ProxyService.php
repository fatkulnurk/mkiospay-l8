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

        Log::info('udata_raw', compact('udataRaw'));
        Log::info('Inquiry', compact('payload'));

        $response = Http::withoutVerifying()
            ->post($url, $payload);
        $responseData = $response->json();

        $respid = $responseData['respid'] ?? null;

        if(blank($respid) || is_null($respid)) {
            Log::alert('inquiry respid null ', $responseData);
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
        $responseData['trxid'] = $trxid;
        $responseData['is_check_status'] = $this->isCheckStatus;

        return $responseData;
    }

//    public function pay($trxid, $productCode, $customerCode, $respid, $options = [])
    public function pay($trxid, $productCode, $customerCode, $options = [])
    {
        $amount = $options['amount'] ?? '';
        $isPbb = $options['is_pbb'] ?? false;

        $transaction = Transaction::where('trxid', $trxid)->first();
//        $transaction = Transaction::where('trxid', $trxid)
//            ->where('product_code', $productCode)
//            ->where('customer_code', $customerCode)
//            ->where('date', now()->setTimezone('Asia/Jakarta')->toDateString())
//            ->first();

        if (!blank($transaction)) {
            return $this->checkStatus($trxid);
        } else {
            $responseData = $this->inquiry($trxid, $productCode, $customerCode, $options);
            $transaction = Transaction::where('trxid', $trxid)->first();
        }

        $respid = $transaction->respid;

        if (blank($respid)) {
            Log::info('Transaction data: ', collect($transaction)->toArray());
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

        Log::info('respid', compact('respid'));
        Log::info('udata_raw', compact('udataRaw'));
        Log::info('Pay', compact('payload'));

        $response = Http::withoutVerifying()
            ->post($url, $payload);
        $responseData = $response->json();
        $responseData['trxid'] = $trxid;
        $responseData['is_check_status'] = $this->isCheckStatus;

        return $responseData;
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

        Log::info('Purchase', compact('payload'));

        $response = Http::withoutVerifying()
            ->post($url, $payload);
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
        $responseData['trxid'] = $trxid;
        $responseData['is_check_status'] = $this->isCheckStatus;

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

        Log::info('Cek status ', compact('payload'));
        $response = Http::withoutVerifying()->post($url, $payload);
        $responseData = $response->json();
        $responseData['trxid'] = $trxid;
        $responseData['is_check_status'] = true;

        return $responseData;
    }
}
