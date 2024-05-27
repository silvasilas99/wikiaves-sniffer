<?php

namespace App\Domain\Sniffer;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use \DOMDocument;

class SnifferSessionService
{
    public const DNS_DOMAIN = "www.wikiaves.com.br";
    public const IP_DOMAIN = "54.174.101.92:443";

    public const FIRST_STEP_FILENAME = "buscaavancada.php";
    private const FIRST_STEP_URL = self::DNS_DOMAIN . "/" . self::FIRST_STEP_FILENAME;

    public const SECOND_STEP_FILENAME = "midias.php";
    private const SECOND_STEP_URL = self::DNS_DOMAIN . "/" . self::SECOND_STEP_FILENAME . "?tm=f&t=b";

    public const CONTINUOS_STEP_FILENAME = "getRegistrosJSON.php";
    private const CONTINUOS_STEP_URL = self::DNS_DOMAIN . "/" . self::CONTINUOS_STEP_FILENAME . "?tm=f&t=b&o=dp&o=dp&desc=1&p=1";

    private const COOKIE_BYTES_AMOUNT = 16;

    private const USER_AGENT = "SilvaSilas99\WikiAvesDataExtractor\App\Domain\Sniffer\SnifferSessionService: 0.0.1";

    private const DEFAULT_OPTIONS = [
        "User-Agent" => self::USER_AGENT,
        "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
        "Accept-Language" => "pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3",
        "Accept-Encoding" => "gzip, deflate, br, zstd",
        "Connection" => "keep-alive",
        "Upgrade-Insecure-Requests" => "1",
        "Sec-Fetch-Dest" => "document",
        "Sec-Fetch-Mode" => "navigate",
        "Sec-Fetch-Site" => "same-origin",
        "Priority" => "u=1",
        "Pragma" => "no-cache",
        "Cache-Control" => "no-cache"
    ];

    // public function createCookie ()
    // {
    //     $response = Http::withHeaders([
    //         "Host" => self::DNS_DOMAIN,
    //         "Referer" => self::DNS_DOMAIN,
    //         "Cookie" => "WIKILANG=pt-br",
    //         ...self::DEFAULT_OPTIONS
    //     ])->post($firstStepUrl);

    //     $responseCookies = data_get($response->getHeaders(), "Set-Cookie");
    //     $sessionCookie =
    //         collect($responseCookies)->map(
    //             function ($item) {
    //                 $item = strtok($item, "PHPSESSID=");
    //                 return strtok($item, ";");
    //             }
    //         )->filter()->first();

    //     //echo "[debug] SnifferSessionService.createCookie: {$sessionCookie} created.\n\n";
    //     return (string)$sessionCookie;
    // }

    public function getTrustedCookieFromWiki(
        array $locale,
        string $startDate,
        string $endDate
    ) : string {
        $boundaryNumber = rand(0, (int)"9999999999999999999999999999");

        $response = Http::withHeaders([
                "Host" => "www.wikiaves.com.br",
                "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
                "Accept-Language" => "pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3",
                "Accept-Encoding" => "gzip, deflate, br, zstd",
                "Referer" => "https://www.wikiaves.com.br/buscaavancada.php",
                "Content-Type" => "multipart/form-data; boundary={$boundaryNumber}",
                "Content-Length" => 1604,
                "Origin" => "https://www.wikiaves.com.br",
                "Connection" => "keep-alive",
                "Cookie" => "WIKILANG=pt-br",
                "Upgrade-Insecure-Requests" => 1,
                "Sec-Fetch-Dest" => "document",
                "Sec-Fetch-Mode" => "navigate",
                "Sec-Fetch-Site" => "same-origin",
                "Sec-Fetch-User" => "?1",
                "Priority" => "u=1"
            ])
            ->post(
                "https://www.wikiaves.com.br/midias.php?tm=f&t=b",
                [
                    "tipoAssuntoAve" => 1,
                    "cidade" => data_get($locale, "city.name", ""),
                    "estado" => data_get($locale, "uf_code"),
                    "cidade_estado" => data_get($locale, "uf_code"),
                    "cidade_hidden" =>  data_get($locale, "city.id"),
                    "dataInicioRegistro" => $startDate,
                    "dataFimRegistro" => $endDate
                ]
            );

        $trustedCookie = $this->getTrustedCookieFromResponse($response);
        $this->storeCookieOnJar($trustedCookie);

        return $trustedCookie;
    }

    public function getWikiPaginatedData (string $trustedCookie, int $page = 1) : array
    {
        $response = Http::withHeaders([
                "Host" => "www.wikiaves.com.br",
                "Accept" => "application/json, text/javascript, */*; q=0.01",
                "Accept-Language" => "pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3",
                "Accept-Encoding" => "gzip, deflate, br, zstd",
                "X-Requested-With" => "XMLHttpRequest",
                "Connection" => "keep-alive",
                "Referer" => "https://www.wikiaves.com.br/midias.php?tm=f&t=b",
                "Cookie" => "WIKILANG=pt-br; PHPSESSID={$trustedCookie}",
                "Sec-Fetch-Dest" => "empty",
                "Sec-Fetch-Mode" => "cors",
                "Sec-Fetch-Site" => "same-origin"
            ])
            ->get("https://www.wikiaves.com.br/getRegistrosJSON.php?tm=f&t=b&o=dp&o=dp&desc=1&p={$page}");

        $newTrustedCookie =
            $this->getTrustedCookieFromResponse($response);
        if (!empty($newTrustedCookie)) {
            $trustedCookie = $newTrustedCookie;
            $this->storeCookieOnJar($trustedCookie);
        }

        return [
            "total" => (int)data_get($response, "registros.total"),
            "items" => (array)data_get($response, "registros.itens"),
            "trusted_cookie" => $trustedCookie
        ];
    }

    private function getTrustedCookieFromResponse (
        Response $response
    ) : ?string
    {
        $responseCookies =
            data_get($response->getHeaders(), "Set-Cookie");
        return collect($responseCookies)->map(
            function ($item) {
                $sessionToken = strtok($item, "PHPSESSID=");
                return (string) strtok($sessionToken, ";");
            }
        )->filter()->first();
    }

    private function storeCookieOnJar(string $trustedCookie)
    {
        setcookie(
            "PHPSESSID",
            $trustedCookie,
            time() + (86400 * 30),
            "/",
            "www.wikiaves.com.br"
        );
        setcookie(
            "WIKILANG",
            "pt-br",
            time() + (86400 * 30),
            "/",
            "www.wikiaves.com.br"
        );
    }
}
