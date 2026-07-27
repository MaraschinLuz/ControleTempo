<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportProjectScheduleRequest;
use App\Http\Requests\ProjectScheduleRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectScheduleColumnService;
use App\Services\ProjectScheduleSpreadsheetImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class ProjectScheduleController extends Controller
{
    public function index(Request $request, ProjectScheduleColumnService $columnService)
    {
        $projects = Project::query()
            ->with('client')
            ->orderBy('name')
            ->get();
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'active']);

        $selectedProject = $request->filled('project_id')
            ? Project::query()->with('client')->findOrFail($request->integer('project_id'))
            : null;
        $columns = $selectedProject ? $columnService->forProject($selectedProject) : collect();

        $scheduleRows = $selectedProject
            ? $selectedProject->scheduleRows()
                ->orderBy('position')
                ->get()
                ->map(fn ($row) => [
                    ...Arr::only($row->toArray(), [
                        'column_1',
                        'column_2',
                        'demand',
                        'ai_suggestion',
                        'completion_status',
                        'responsible',
                        'client_responsible',
                        'client_contact',
                        'scope',
                        'completed_demands',
                        'remaining_work',
                        'hours',
                    ]),
                    'execution_date' => $row->execution_date?->format('Y-m-d'),
                    'completion_date' => $row->completion_date?->format('Y-m-d'),
                    'custom_data' => $row->custom_data ?? [],
                ])
                ->values()
            : collect();

        return view('project-schedules.index', compact('projects', 'users', 'selectedProject', 'columns', 'scheduleRows'));
    }

    public function update(ProjectScheduleRequest $request, Project $project)
    {
        $rows = $request->validated('rows', []);

        $this->replaceRows($project, $rows);

        return redirect()
            ->route('project-schedules.index', ['project_id' => $project->id])
            ->with('success', 'Cronograma salvo com sucesso.');
    }

    public function import(
        ImportProjectScheduleRequest $request,
        Project $project,
        ProjectScheduleSpreadsheetImporter $importer,
    ) {
        try {
            $import = $importer->import($request->file('spreadsheet')->getRealPath());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['spreadsheet' => $exception->getMessage()]);
        }

        $scheduleRequest = new ProjectScheduleRequest;
        $rows = Validator::make(
            ['rows' => $import['rows']],
            $scheduleRequest->rules(),
            $scheduleRequest->messages(),
        )->validate()['rows'];

        $this->replaceRows($project, $rows);
        $rowCount = count($rows);
        $rowLabel = $rowCount === 1 ? 'linha importada' : 'linhas importadas';

        return redirect()
            ->route('project-schedules.index', ['project_id' => $project->id])
            ->with('success', "{$rowCount} {$rowLabel} da aba \"{$import['worksheet']}\".");
    }

    private function replaceRows(Project $project, array $rows): void
    {
        DB::transaction(function () use ($project, $rows) {
            $project->scheduleRows()->delete();

            foreach (array_values($rows) as $index => $row) {
                $project->scheduleRows()->create([
                    ...$row,
                    'position' => $index + 1,
                ]);
            }
        });
    }
}
