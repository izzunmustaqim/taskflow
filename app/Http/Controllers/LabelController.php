<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabelRequest;
use App\Http\Requests\UpdateLabelRequest;
use App\Models\Label;
use App\Services\LabelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class LabelController extends Controller
{
    public function __construct(
        private readonly LabelService $labelService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Label::class);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $labels = $this->labelService->list($user);

        return Inertia::render('Labels/Index', [
            'labels' => $labels,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Label::class);

        return Inertia::render('Labels/Create');
    }

    public function store(StoreLabelRequest $request): RedirectResponse
    {
        Gate::authorize('create', Label::class);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $this->labelService->create($user, $request->validated());

        return redirect()->route('labels.index')
            ->with('success', 'Label created successfully.');
    }

    public function edit(Request $request, Label $label): Response
    {
        Gate::authorize('update', $label);

        return Inertia::render('Labels/Edit', [
            'label' => $label,
        ]);
    }

    public function update(UpdateLabelRequest $request, Label $label): RedirectResponse
    {
        Gate::authorize('update', $label);

        $this->labelService->update($label, $request->validated());

        return redirect()->route('labels.index')
            ->with('success', 'Label updated successfully.');
    }

    public function destroy(Label $label): RedirectResponse
    {
        Gate::authorize('delete', $label);

        $this->labelService->delete($label);

        return redirect()->route('labels.index')
            ->with('success', 'Label deleted successfully.');
    }
}
