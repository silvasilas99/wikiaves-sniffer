<?php

namespace App\Console\Commands;

use App;
use App\Domain\Sniffer\SnifferExportationRecord;
use App\Domain\Sniffer\SnifferSessionService;
use App\Jobs\ProcessSnifferExportationRecord;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "app:test-command";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Runs a test command.";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $snifferSessionService = App::make(SnifferSessionService::class);

        $startDate = "01/01/2000";
        $endDate = "26/05/2024";

        // $localesToRun = [
        //     [
        //         "city" => [
        //             "name" => "Manaus",
        //             "id" => 1302603
        //         ],
        //         "uf_code" => "AM"
        //     ],
        //     [
        //         "city" => [
        //             "name" => "Salvador",
        //             "id" => 2927408
        //         ],
        //         "uf_code" => "BA"
        //     ],
        // ];

        $trustedCookie =
            $this->ask("Insert a valid token: ");
            //$snifferSessionService->getTrustedCookieFromWiki($locale, $startDate, $endDate);
        $cityName =
            $this->ask("Insert the name of the city: ");
        $cityId =
            $this->ask("Insert ID of the city: ");
        $ufCode =
            $this->ask("Insert the UF code: ");

        $data =
            $snifferSessionService->getWikiPaginatedData($trustedCookie);

        $exportationRecord =
            SnifferExportationRecord::create([
                "items_amount" => data_get($data, "total", 0),
                "items_data" => json_encode(data_get($data, "items", [])),
                "title" => "Teste"
            ]);

        $locale = [
            "city" => [
                "name" => $cityName,
                "id" => $cityId
            ],
            "uf_code" => $ufCode
        ];

        ProcessSnifferExportationRecord::dispatch(
            $exportationRecord,
            $trustedCookie,
            $locale,
            $startDate,
            $endDate
        );

        // data_set($exportationRecord, "exportation_database_file_path", "teste");
        // $exportationRecord->save();

        // foreach ($localesToRun as $locale) {
        // }
    }
}
