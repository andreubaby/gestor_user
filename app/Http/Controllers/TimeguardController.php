<?php

namespace App\Http\Controllers;

use App\Models\TimeguardAuditLog;
use App\Models\TimeguardCompensation;
use App\Models\TimeguardTimeEntry;
use App\Models\TimeguardWorker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimeguardController extends Controller
{
    // ─── Helpers ────────────────────────────────────────────────────────────

    private function workerToJson(TimeguardWorker $w): array
    {
        return ['id' => $w->id, 'name' => $w->name, 'isActive' => $w->is_active];
    }

    private function entryToJson(TimeguardTimeEntry $e): array
    {
        $logs = $e->logs->map(fn($l) => [
            'id'        => $l->id,
            'timestamp' => $l->timestamp,
            'field'     => $l->field,
            'oldValue'  => $l->old_value,
            'newValue'  => $l->new_value,
            'user'      => $l->user,
            'reason'    => $l->reason,
        ])->values()->all();

        return [
            'id'            => $e->id,
            'workerId'      => $e->worker_id,
            'date'          => $e->date,
            'hoursBrutas'   => $e->hours_brutas,
            'lunchDiscount' => $e->lunch_discount,
            'isLunchManual' => $e->is_lunch_manual,
            'isFreeDay'     => $e->is_free_day,
            'notes'         => $e->notes,
            'logs'          => $logs,
        ];
    }

    private function compensationToJson(TimeguardCompensation $c): array
    {
        return [
            'id'       => $c->id,
            'workerId' => $c->worker_id,
            'date'     => $c->date,
            'type'     => $c->type,
            'minutes'  => $c->minutes,
            'notes'    => $c->notes,
        ];
    }

    // ─── Workers ─────────────────────────────────────────────────────────────

    public function listWorkers(): JsonResponse
    {
        $workers = TimeguardWorker::orderBy('name')->get();
        return response()->json($workers->map(fn($w) => $this->workerToJson($w))->values());
    }

    public function storeWorker(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'       => 'required|string|max:100',
            'name'     => 'required|string|max:255',
            'isActive' => 'boolean',
        ]);

        $worker = TimeguardWorker::create([
            'id'        => $data['id'],
            'name'      => $data['name'],
            'is_active' => $data['isActive'] ?? true,
        ]);

        return response()->json($this->workerToJson($worker), 201);
    }

    public function updateWorker(Request $request, string $id): JsonResponse
    {
        $worker = TimeguardWorker::findOrFail($id);

        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'isActive' => 'sometimes|boolean',
        ]);

        if (isset($data['name']))     $worker->name      = $data['name'];
        if (isset($data['isActive'])) $worker->is_active = $data['isActive'];
        $worker->save();

        return response()->json($this->workerToJson($worker));
    }

    public function destroyWorker(string $id): JsonResponse
    {
        TimeguardWorker::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    // ─── Time Entries ─────────────────────────────────────────────────────────

    public function listEntries(Request $request): JsonResponse
    {
        $workerId = $request->query('worker_id');
        $entries  = TimeguardTimeEntry::with('logs')
            ->where('worker_id', $workerId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($entries->map(fn($e) => $this->entryToJson($e))->values());
    }

    public function storeEntry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'            => 'required|string|max:100',
            'workerId'      => 'required|string|exists:timeguard_workers,id',
            'date'          => 'required|date',
            'hoursBrutas'   => 'required|integer|min:0',
            'lunchDiscount' => 'required|integer|min:0',
            'isLunchManual' => 'boolean',
            'isFreeDay'     => 'boolean',
            'notes'         => 'nullable|string',
            'logs'          => 'array',
        ]);

        $entry = TimeguardTimeEntry::create([
            'id'             => $data['id'],
            'worker_id'      => $data['workerId'],
            'date'           => $data['date'],
            'hours_brutas'   => $data['hoursBrutas'],
            'lunch_discount' => $data['lunchDiscount'],
            'is_lunch_manual'=> $data['isLunchManual'] ?? false,
            'is_free_day'    => $data['isFreeDay'] ?? false,
            'notes'          => $data['notes'] ?? null,
        ]);

        foreach ($data['logs'] ?? [] as $log) {
            TimeguardAuditLog::create([
                'id'        => $log['id'],
                'entry_id'  => $entry->id,
                'timestamp' => $log['timestamp'],
                'field'     => $log['field'],
                'old_value' => $log['oldValue'] ?? null,
                'new_value' => $log['newValue'] ?? null,
                'user'      => $log['user'] ?? null,
                'reason'    => $log['reason'] ?? null,
            ]);
        }

        $entry->load('logs');
        return response()->json($this->entryToJson($entry), 201);
    }

    public function updateEntry(Request $request, string $id): JsonResponse
    {
        $entry = TimeguardTimeEntry::findOrFail($id);

        $data = $request->validate([
            'date'          => 'sometimes|date',
            'hoursBrutas'   => 'sometimes|integer|min:0',
            'lunchDiscount' => 'sometimes|integer|min:0',
            'isLunchManual' => 'sometimes|boolean',
            'isFreeDay'     => 'sometimes|boolean',
            'notes'         => 'nullable|string',
        ]);

        $entry->update([
            'date'           => $data['date']          ?? $entry->date,
            'hours_brutas'   => $data['hoursBrutas']   ?? $entry->hours_brutas,
            'lunch_discount' => $data['lunchDiscount'] ?? $entry->lunch_discount,
            'is_lunch_manual'=> $data['isLunchManual'] ?? $entry->is_lunch_manual,
            'is_free_day'    => $data['isFreeDay']     ?? $entry->is_free_day,
            'notes'          => $data['notes']         ?? $entry->notes,
        ]);

        $entry->load('logs');
        return response()->json($this->entryToJson($entry));
    }

    public function destroyEntry(string $id): JsonResponse
    {
        TimeguardTimeEntry::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    // ─── Compensations ───────────────────────────────────────────────────────

    public function listCompensations(Request $request): JsonResponse
    {
        $workerId       = $request->query('worker_id');
        $compensations  = TimeguardCompensation::where('worker_id', $workerId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($compensations->map(fn($c) => $this->compensationToJson($c))->values());
    }

    public function storeCompensation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'       => 'required|string|max:100',
            'workerId' => 'required|string|exists:timeguard_workers,id',
            'date'     => 'required|date',
            'type'     => 'required|in:PAYMENT,REST_HOURS,FREE_DAY',
            'minutes'  => 'required|integer|min:1',
            'notes'    => 'nullable|string',
        ]);

        $comp = TimeguardCompensation::create([
            'id'        => $data['id'],
            'worker_id' => $data['workerId'],
            'date'      => $data['date'],
            'type'      => $data['type'],
            'minutes'   => $data['minutes'],
            'notes'     => $data['notes'] ?? null,
        ]);

        return response()->json($this->compensationToJson($comp), 201);
    }

    public function destroyCompensation(string $id): JsonResponse
    {
        TimeguardCompensation::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    // ─── Bulk import desde localStorage ─────────────────────────────────────

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'workers'               => 'required|array',
            'entries'               => 'required|array',
            'compensations'         => 'required|array',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['workers'] as $w) {
                TimeguardWorker::updateOrCreate(['id' => $w['id']], [
                    'name'      => $w['name'],
                    'is_active' => $w['isActive'] ?? true,
                ]);
            }

            foreach ($data['entries'] as $e) {
                $entry = TimeguardTimeEntry::updateOrCreate(['id' => $e['id']], [
                    'worker_id'      => $e['workerId'],
                    'date'           => $e['date'],
                    'hours_brutas'   => $e['hoursBrutas']   ?? 0,
                    'lunch_discount' => $e['lunchDiscount'] ?? 0,
                    'is_lunch_manual'=> $e['isLunchManual'] ?? false,
                    'is_free_day'    => $e['isFreeDay']     ?? false,
                    'notes'          => $e['notes']         ?? null,
                ]);

                foreach ($e['logs'] ?? [] as $log) {
                    TimeguardAuditLog::updateOrCreate(['id' => $log['id']], [
                        'entry_id'  => $entry->id,
                        'timestamp' => $log['timestamp'],
                        'field'     => $log['field'],
                        'old_value' => $log['oldValue'] ?? null,
                        'new_value' => $log['newValue'] ?? null,
                        'user'      => $log['user']     ?? null,
                        'reason'    => $log['reason']   ?? null,
                    ]);
                }
            }

            foreach ($data['compensations'] as $c) {
                TimeguardCompensation::updateOrCreate(['id' => $c['id']], [
                    'worker_id' => $c['workerId'],
                    'date'      => $c['date'],
                    'type'      => $c['type'],
                    'minutes'   => $c['minutes'],
                    'notes'     => $c['notes'] ?? null,
                ]);
            }
        });

        return response()->json(['ok' => true, 'imported' => [
            'workers'       => count($data['workers']),
            'entries'       => count($data['entries']),
            'compensations' => count($data['compensations']),
        ]]);
    }
}

