<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Services\MonthlyBalanceService;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categoriesIncome = Category::where('type', 'income')
                                ->where('user_id', $request->user()->id)
                                ->get();

        $categoriesExpense = Category::where('type', 'expense')
                                ->where('user_id', $request->user()->id)
                                ->get();

        return view('categories.index', compact('categoriesIncome', 'categoriesExpense'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $groups = Group::where('user_id', $request->user()->id)->get();
     
        return view('categories.create', compact('groups'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validateCategory($request);

        Category::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'group_id' => $data['group_id'] ?? null,
        ]);

        return redirect()->route('categories.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id, Request $request)
    {
        $category = Category::where('user_id', $request->user()->id)->findOrFail($id);
        $groups = Group::where('user_id', $request->user()->id)->get();
        
        return view('categories.edit', compact('category', 'groups'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::where('user_id', $request->user()->id)->findOrFail($id);
        $data = $this->validateCategory($request);

        $category->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'group_id' => $data['group_id'] ?? null,
        ]);

        return redirect()->route('categories.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $category = Category::where('user_id', $request->user()->id)->findOrFail($id);

        abort_if($category->expenses()->where('user_id', '!=', $request->user()->id)->exists(), 409);
        abort_if(\App\Models\PluggyTransaction::where('category_id', $category->id)->where('user_id', '!=', $request->user()->id)->exists(), 409);
        DB::transaction(function () use ($category, $request) {
            $category->delete();
            app(MonthlyBalanceService::class)->recalculateClosedFrom($request->user()->id, 0, 1);
        });

        return redirect()->route('categories.index');
    }

    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'group_id' => ['nullable', 'integer', Rule::exists('groups', 'id')->where('user_id', $request->user()->id)],
        ]);
    }
}
