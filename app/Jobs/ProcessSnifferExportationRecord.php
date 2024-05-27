<?php

namespace App\Jobs;

use App\Domain\Sniffer\SnifferExportationRecord;
use App\Domain\Sniffer\SnifferSessionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Log;

class ProcessSnifferExportationRecord implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    //use CsvExporterHandlerTrait;

    /**
     * @var ?SnifferSessionService $snifferSessionService
     */
    private $snifferSessionService = null;

    private array $recordDecodedData = [];

    private $filePointer = null;

    /**
     * Create a new job instance.
     *
     * @var SnifferExportationRecord $snifferExportationRecord
     */
    public function __construct(
        private SnifferExportationRecord $snifferExportationRecord,
        private string $trustedCookie,
        private array $locale
    ) {
        $this->snifferSessionService = App::make(SnifferSessionService::class);
        $this->recordDecodedData = json_decode(data_get($snifferExportationRecord, "items_data", ""), true);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $currentTimestamp = time();
        $localName =
            data_get($this->locale, "city.name", "") . "-" . data_get($this->locale, "uf_code", "");

        $filename = "{$localName}-{$currentTimestamp}.csv";

        $filepath = public_path("csv_archives") . "/" . $filename;

        $filePointer = fopen($filepath, "a+b");

        $header = [ "id", "sp.id", "sp.nome", "sp.nvt", "sp.idwiki", "autor", "perfil", "data", "local", "idMunicipio", "link" ];
        fputcsv($filePointer, $header);

        $itemsAmount = (int)data_get($this->snifferExportationRecord, "items_amount", 0);
        $obtainedItemsAmount = count($this->recordDecodedData);

        $this->writeItemsDataOnCSV($this->recordDecodedData, $filePointer);

        $itemsPage = 2;


        while ($itemsAmount > $obtainedItemsAmount)
        {
            $data =
                $this->snifferSessionService->getWikiPaginatedData($this->trustedCookie, $itemsPage);

            $itemsDataFromNewRequest = data_get($data, "items", []);
            if(empty($itemsDataFromNewRequest)) {
                Log::warning("[WARNING] ProcessSnifferExportationRecord.handle: @var itemsDataFromNewRequest is empty.");
                return;
            }

            $this->writeItemsDataOnCSV($itemsDataFromNewRequest, $filePointer);

            $obtainedItemsAmount =
                count ($itemsDataFromNewRequest) + $obtainedItemsAmount;

            $itemsPage = $itemsPage + 1;

            Log::debug("[DEBUG] ProcessSnifferExportationRecord.handle", [
                "message:" => "ROUND {$itemsPage} FINISHED"
            ]);
        }

        fclose($filePointer);
    }

    private function writeItemsDataOnCSV (array $itemsData, $filePointer) :void
    {
        foreach ($itemsData as $item) {
            if (empty($item)) {
                continue;
            }

            fputcsv(
                $filePointer,
                [
                    data_get($item, "id", ""),
                    data_get($item, "sp.id", ""),
                    data_get($item, "sp.nome", ""),
                    data_get($item, "sp.nvt", ""),
                    data_get($item, "sp.idwiki", ""),
                    data_get($item, "autor", ""),
                    data_get($item, "perfil", ""),
                    data_get($item, "data", ""),
                    data_get($item, "local", ""),
                    data_get($item, "idMunicipio", ""),
                    data_get($item, "link", "")
                ]
            );
        }
    }
}
