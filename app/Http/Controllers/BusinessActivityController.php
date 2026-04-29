<?php

namespace App\Http\Controllers;

use App\Models\BusinessActivity;
use App\Models\Sector;

class BusinessActivityController extends Controller
{
    /**
     * Obtiene el listado completo de giros (paginado).
     */
    public function index()
    {
        $activities = BusinessActivity::with('sector')->paginate(50);

        return response()->json($activities);
    }

    /**
     * Obtiene todos los sectores/rubros disponibles.
     */
    public function getSectors()
    {
        $sectors = Sector::orderBy('name', 'asc')->get();

        return response()->json($sectors);
    }

    /**
     * Filtra los giros por sector (ID).
     */
    public function filterBySector($sectorId)
    {
        $activities = BusinessActivity::where('sector_id', $sectorId)->paginate(50);

        return response()->json($activities);
    }
}
