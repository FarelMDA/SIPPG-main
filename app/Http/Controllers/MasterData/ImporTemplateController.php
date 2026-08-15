<?php

namespace App\Http\Controllers\MasterData;

use App\Exports\GenerusTemplateExport;
use App\Exports\PendidikTemplateExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImporTemplateController extends Controller
{
    public function __invoke(Request $request, string $tipe)
    {
        abort_unless(in_array($tipe, ['generus', 'pendidik'], true), 404);

        $export = $tipe === 'generus' ? new GenerusTemplateExport : new PendidikTemplateExport;

        return Excel::download($export, "template-impor-{$tipe}.xlsx");
    }
}
