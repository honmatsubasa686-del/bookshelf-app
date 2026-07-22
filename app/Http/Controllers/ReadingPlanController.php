<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    public function index(): View
    {
        $currentStatus = request('status');

        $readingPlans = auth()->user()
            ->readingPlans()
            ->with('book')
            ->when($currentStatus, function ($query, $currentStatus) {
                $query->where('status', $currentStatus);
            })
            ->latest()
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    public function create(): View
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        ReadingPlan::create([
            'user_id' => auth()->id(),
            'book_id' => $request->validated('book_id'),
            'due_date' => $request->validated('due_date'),
            'status' => ReadingPlanStatus::Planned,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を作成しました。');
    }

    public function edit(ReadingPlan $readingPlan): View
    {
        abort_unless($readingPlan->user_id === auth()->id(), 403);
        abort_if($readingPlan->status !== ReadingPlanStatus::Planned, 403);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    public function update(UpdateReadingPlanRequest $request, ReadingPlan $readingPlan): RedirectResponse
    {
        abort_unless($readingPlan->user_id === auth()->id(), 403);
        abort_if($readingPlan->status !== ReadingPlanStatus::Planned, 403);

        $readingPlan->update([
            'due_date' => $request->validated('due_date'),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        abort_unless($readingPlan->user_id === auth()->id(), 403);

        if ($readingPlan->status === ReadingPlanStatus::Completed) {
            return redirect()
                ->route('reading-plans.index')
                ->with('success', '読書計画はすでに読了済みです。');
        }

        abort_if($readingPlan->status === ReadingPlanStatus::Expired, 403);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を読了しました。');
    }

    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        abort_unless($readingPlan->user_id === auth()->id(), 403);

        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }
}
