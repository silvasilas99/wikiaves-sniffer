<?php

namespace App\Domain\Sniffer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// header("content-type: text/plain;charset=utf8");

// require "vendor/autoload.php";

// use DOMDocument;

class SnifferController extends Controller
{
    private string $sessionCookie          = "";

    public function __construct(
        private ?SnifferSessionService $snifferSessionService = null
    ) {
        //
    }

    public function exportDataFromAdvancedSearch (Request $request)
    {     // : JsonResponse {
        //$sessionCookie = $this->snifferSessionService->createCookie();
        $trustedCookie = $this->snifferSessionService->getTrustedCookieFromWiki();
        dd($trustedCookie);
        //session_set_cookie_params()
        // $paginatedData = $this->snifferSessionService->getWikiPaginatedData($trustedCookie);
        // dd($paginatedData);
    }

    public function findAllData (Request $request)
    {
        //
    }
}
