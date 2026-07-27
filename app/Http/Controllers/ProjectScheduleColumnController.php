<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectScheduleColumnRequest;
use App\Models\Project;
use App\Models\ProjectScheduleColumn;
use App\Services\ProjectScheduleColumnService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectScheduleColumnController extends Controller
{
    public function store(
        StoreProjectScheduleColumnRequest $request,
        Project $project,
        ProjectScheduleColumnService $columns,
    ) {
        $columns->create($project, $request->validated());

        return back()->with('success', 'Coluna adicionada ao cronograma.');
    }

    public function move(
        Request $request,
        Project $project,
        ProjectScheduleColumn $scheduleColumn,
        ProjectScheduleColumnService $columns,
    ) {
        $data = $request->validate([
            'direction' => ['required', Rule::in(['left', 'right'])],
        ]);
        $columns->move($project, $scheduleColumn, $data['direction']);

        return back()->with('success', 'Ordem das colunas atualizada.');
    }

    public function destroy(
        Project $project,
        ProjectScheduleColumn $scheduleColumn,
        ProjectScheduleColumnService $columns,
    ) {
        $columns->delete($project, $scheduleColumn);

        return back()->with('success', 'Coluna removida do cronograma.');
    }
}
