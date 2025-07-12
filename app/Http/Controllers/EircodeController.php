<?php
namespace App\Http\Controllers;

use App\Services\EircodeService;

class EircodeController extends Controller
{
    protected $eircodeService;

    public function __construct(EircodeService $eircodeService)
    {
        $this->eircodeService = $eircodeService;
    }

    public function getEircodeArea($eircode)
    {
        $eircodes = $this->eircodeService->getEircodeArea($eircode);
        return response()->json($eircodes);
    }
}
