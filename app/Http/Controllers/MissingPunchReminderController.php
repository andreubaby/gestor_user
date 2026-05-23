<?php

namespace App\Http\Controllers;

use App\Services\MissingPunchReminderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MissingPunchReminderController extends Controller
{
    public function index(Request $request, MissingPunchReminderService $service): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $targetDate = null;

        if (!empty($validated['date'])) {
            $targetDate = Carbon::createFromFormat('Y-m-d', (string) $validated['date'])->startOfDay();
        }

        $report = $service->previewForDate($targetDate);

        return view('automation.missing-punch.preview', [
            'report' => $report,
            'selectedDate' => $validated['date'] ?? $report['date'],
            'messageTemplate' => (string) config(
                'fichajes.missing_punch.message_template',
                'Hola {nombre}, ayer ({fecha}) no aparece ningun fichaje tuyo. Si corresponde, revisalo en la app.'
            ),
        ]);
    }
}

