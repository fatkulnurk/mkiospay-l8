<?php

namespace App\Http\Controllers;

use App\Services\Proxiers\ProxyService;
use Illuminate\Http\Request;

class TelenjarController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, ProxyService $proxyService)
    {
        if ($request->filled('TOKEN') ||
            $request->filled('PLN') ||
            $request->filled('NONTALGIS') ||
            $request->filled('TELKOM') ||
            $request->filled('BPJS') ||
            $request->filled('MULTIFINANCE') ||
            $request->filled('PBB')
        ) {
            switch ($request->TOKEN) {
                case 'INQ':
                    return $proxyService->inquiry(
                        $request->trxid,
                        $request->produk,
                        $request->tujuan,
                        $request->nominal
                    );
                case 'PAY':
                    return $proxyService->pay(
                        $request->trxid,
                        $request->produk,
                        $request->tujuan,
                        $request->respid,
                        $request->nominal
                    );
                default: throw new \Exception('Invalid parameter ' . $request->TOKEN);
            }
        }

        if ($request->filled('CHECK')) {
            return $proxyService->checkStatus($request->trxid);
        }

        return $proxyService->purchase(
            $request->trxid,
            $request->produk,
            $request->tujuan,
            $request->nominal
        );
    }
}
