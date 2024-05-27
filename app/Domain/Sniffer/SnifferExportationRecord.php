<?php

namespace App\Domain\Sniffer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SnifferExportationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        "title",
        "items_amount",
        "items_data",
        "exportation_database_file_path",
    ];

    protected $table = "sniffer_exportation_records";
}
