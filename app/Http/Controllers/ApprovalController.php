<?php

namespace App\Http\Controllers;

use App\Actions\ApproveTimeEntryAction;
use App\Actions\RejectTimeEntryAction;
use App\Http\Requests\RejectTimeEntryRequest;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApprovalController extends Controller
{
    public function approve(Request $request, TimeEntry $timeEntry, ApproveTimeEntryAction $action)
    {
        Gate::authorize('approve', $timeEntry);
        $action->execute($timeEntry, $request->user());

        return back()->with('success', 'Registro aprovado.');
    }

    public function reject(RejectTimeEntryRequest $request, TimeEntry $timeEntry, RejectTimeEntryAction $action)
    {
        $action->execute($timeEntry, $request->user(), $request->string('rejection_reason')->toString());

        return back()->with('success', 'Registro rejeitado.');
    }
}
