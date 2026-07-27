<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectScheduleColumn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectScheduleColumnService
{
    private const MAX_COLUMNS = 40;

    private const DEFAULT_COLUMNS = [
        ['column_key' => 'column_1', 'label' => 'Column 1', 'type' => 'text', 'width' => 80],
        ['column_key' => 'column_2', 'label' => 'Column 2', 'type' => 'text', 'width' => 70],
        ['column_key' => 'demand', 'label' => 'Demandas', 'type' => 'textarea', 'width' => 340],
        ['column_key' => 'ai_suggestion', 'label' => 'Sugestão IA', 'type' => 'textarea', 'width' => 360],
        ['column_key' => 'completion_status', 'label' => 'Foi feito?', 'type' => 'status', 'width' => 165],
        ['column_key' => 'execution_date', 'label' => 'Data Execução', 'type' => 'date', 'width' => 160],
        ['column_key' => 'responsible', 'label' => 'Responsável', 'type' => 'user', 'width' => 220],
        ['column_key' => 'client_responsible', 'label' => 'Responsável Cliente', 'type' => 'text', 'width' => 220],
        ['column_key' => 'client_contact', 'label' => 'Contato Cliente', 'type' => 'text', 'width' => 190],
        ['column_key' => 'scope', 'label' => 'Escopo', 'type' => 'textarea', 'width' => 220],
        ['column_key' => 'completed_demands', 'label' => 'Demandas realizadas', 'type' => 'textarea', 'width' => 250],
        ['column_key' => 'remaining_work', 'label' => 'O que falta', 'type' => 'textarea', 'width' => 250],
        ['column_key' => 'completion_date', 'label' => 'Quando finaliza', 'type' => 'date', 'width' => 180],
        ['column_key' => 'hours', 'label' => 'Quantidade de horas', 'type' => 'number', 'width' => 190],
    ];

    public function forProject(Project $project): Collection
    {
        if (! $project->scheduleColumns()->exists()) {
            DB::transaction(function () use ($project) {
                foreach (self::DEFAULT_COLUMNS as $index => $column) {
                    $project->scheduleColumns()->create([
                        ...$column,
                        'is_custom' => false,
                        'position' => $index + 1,
                    ]);
                }
            });
        }

        return $project->scheduleColumns()->orderBy('position')->get();
    }

    public function create(Project $project, array $data): ProjectScheduleColumn
    {
        $columns = $this->forProject($project);

        if ($columns->contains(fn (ProjectScheduleColumn $column) => Str::lower($column->label) === Str::lower($data['label']))) {
            throw ValidationException::withMessages([
                'label' => 'Já existe uma coluna com esse nome neste projeto.',
            ]);
        }

        if ($columns->count() >= self::MAX_COLUMNS) {
            throw ValidationException::withMessages([
                'label' => 'O cronograma pode ter no máximo '.self::MAX_COLUMNS.' colunas.',
            ]);
        }

        return $project->scheduleColumns()->create([
            'column_key' => 'custom_'.Str::lower(Str::random(16)),
            'label' => $data['label'],
            'type' => $data['type'],
            'width' => $data['type'] === 'textarea' ? 280 : 200,
            'is_custom' => true,
            'position' => $columns->count() + 1,
        ]);
    }

    public function move(Project $project, ProjectScheduleColumn $column, string $direction): void
    {
        $this->assertBelongsToProject($project, $column);
        $this->forProject($project);

        $neighbor = $project->scheduleColumns()
            ->where('position', $direction === 'left' ? '<' : '>', $column->position)
            ->orderBy('position', $direction === 'left' ? 'desc' : 'asc')
            ->first();

        if (! $neighbor) {
            return;
        }

        DB::transaction(function () use ($column, $neighbor) {
            $position = $column->position;
            $neighborPosition = $neighbor->position;
            $column->update(['position' => 0]);
            $neighbor->update(['position' => $position]);
            $column->update(['position' => $neighborPosition]);
        });
    }

    public function delete(Project $project, ProjectScheduleColumn $column): void
    {
        $this->assertBelongsToProject($project, $column);

        if (! $column->is_custom) {
            throw ValidationException::withMessages([
                'column' => 'As colunas padrão não podem ser removidas.',
            ]);
        }

        DB::transaction(function () use ($project, $column) {
            $column->delete();

            $project->scheduleColumns()
                ->orderBy('position')
                ->get()
                ->each(fn (ProjectScheduleColumn $item, int $index) => $item->update(['position' => $index + 1]));
        });
    }

    private function assertBelongsToProject(Project $project, ProjectScheduleColumn $column): void
    {
        abort_unless($column->project_id === $project->id, 404);
    }
}
