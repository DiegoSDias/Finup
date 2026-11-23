<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $groups = Group::with(['categories' => fn ($query) => $query->where('user_id', $request->user()->id)])
                        ->where('user_id', $request->user()->id)->get();

        return view('groups.index', compact('groups'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        
        $incomeCategories = Category::where('user_id', $request->user()->id)
            ->where('type', 'income')
            ->where('group_id', null)
            ->get();

        $expenseCategories = Category::where('user_id', $request->user()->id)
            ->where('type', 'expense')
            ->where('group_id', null)
            ->get();

        return view('groups.create', compact('incomeCategories', 'expenseCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validateGroup($request);
        DB::transaction(function () use ($request, $data) {
            $group = Group::create(['user_id' => $request->user()->id, 'name' => $data['name']]);
            Category::where('user_id', $request->user()->id)
                ->whereIn('id', array_merge($data['income_categories'] ?? [], $data['expense_categories'] ?? []))
                ->update(['group_id' => $group->id]);
        });

        return redirect()->route('category-groups.index');
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
        $group = Group::with(['categories' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->where('user_id', $request->user()->id)->findOrFail($id);


        $incomeCategories = Category::where('user_id', $request->user()->id)
            ->where('type', 'income')
            ->where(function ($query) use ($group) {
                $query->whereNull('group_id')
                    ->orWhere('group_id', $group->id);
            })
            ->get();

        $expenseCategories = Category::where('user_id', $request->user()->id)
            ->where('type', 'expense')
            ->where(function ($query) use ($group) {
                $query->whereNull('group_id')
                    ->orWhere('group_id', $group->id);
            })
            ->get();

        return view('groups.edit', compact(
            'group',
            'incomeCategories',
            'expenseCategories'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $group = Group::where('user_id', $request->user()->id)->findOrFail($id);
        $data = $this->validateGroup($request);
        DB::transaction(function () use ($request, $group, $data) {
            $group->update(['name' => $data['name']]);
            Category::where('user_id', $request->user()->id)
                ->whereIn('id', array_merge($data['income_categories'] ?? [], $data['expense_categories'] ?? []))
                ->update(['group_id' => $group->id]);
        });

        return redirect()->route('category-groups.index');
        
        }
        
        /**
         * Remove the specified resource from storage.
        */
        public function destroy(Request $request, string $id)
        {
            $group = Group::where('user_id', $request->user()->id)->findOrFail($id);
            abort_if($group->categories()->where('user_id', '!=', $request->user()->id)->exists(), 409);
            $group->delete();

        return redirect()->route('category-groups.index');
    }

    private function validateGroup(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'income_categories' => ['sometimes', 'array'],
            'income_categories.*' => ['required', 'integer', 'distinct', Rule::exists('categories', 'id')->where('user_id', $request->user()->id)->where('type', 'income')],
            'expense_categories' => ['sometimes', 'array'],
            'expense_categories.*' => ['required', 'integer', 'distinct', Rule::exists('categories', 'id')->where('user_id', $request->user()->id)->where('type', 'expense')],
        ]);
    }
}
