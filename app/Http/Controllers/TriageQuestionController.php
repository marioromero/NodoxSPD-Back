<?php

namespace App\Http\Controllers;

use App\Models\TriageQuestion;
use Illuminate\Http\Request;

class TriageQuestionController extends Controller
{
    /**
     * Display a listing of the resource filtered by module.
     */
    public function index(Request $request)
    {
        $moduleSlug = $request->query('module');

        $questions = TriageQuestion::where('module_slug', $moduleSlug)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Preguntas de triage obtenidas exitosamente.',
            'data' => $questions,
        ]);
    }
}
