<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class CategoryController
{
    public function store(StoreCategoryRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        $category = $project->categories()->create([
            'name' => $data['name'],
            'kind' => $data['kind'],
            'form_schema' => $data['form_schema'] ?? [],
            'default_assignee_id' => $data['default_assignee_id'] ?? null,
            'sort' => $data['sort'] ?? 0,
        ]);
        AuditService::record('category.created', $category);

        return back()->with('status', __('categories.created'));
    }

    public function update(UpdateCategoryRequest $request, Project $project, Category $category): RedirectResponse
    {
        $category->update($request->validated());
        AuditService::record('category.updated', $category);

        return back()->with('status', __('categories.updated'));
    }

    public function destroy(Project $project, Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);
        $category->delete();
        AuditService::record('category.deleted', $category);

        return back()->with('status', __('categories.deleted'));
    }
}
