<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Services\TimeEntryQueryService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimeEntryExportController extends Controller
{
    public function __invoke(Request $request, TimeEntryQueryService $filters): StreamedResponse
    {
        $query = $filters->apply(TimeEntry::query()->with(['user', 'project.client', 'activity'])->visibleTo($request->user()), $request->query());

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Usuário', 'Cliente', 'Projeto', 'Atividade', 'Descrição', 'Início', 'Término', 'Duração (s)', 'Tipo', 'Status'], ';');
            $query->chunk(500, function ($entries) use ($out) {
                foreach ($entries as $entry) {
                    fputcsv($out, [$entry->started_at->format('d/m/Y'), $entry->user->name, $entry->project->client->name, $entry->project->name, $entry->activity->name, $entry->description, $entry->started_at->format('d/m/Y H:i'), $entry->ended_at?->format('d/m/Y H:i'), $entry->duration_seconds, $entry->entry_type->label(), $entry->status->label()], ';');
                }
            });
            fclose($out);
        }, 'horas-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
