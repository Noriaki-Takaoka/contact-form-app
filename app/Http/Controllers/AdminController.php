<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminController extends Controller
{
    /**
     * お問い合わせ一覧を検索条件付きで表示する
     */
    public function index(IndexContactRequest $request): View
    {
        $contacts = Contact::with(['category', 'tags'])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->input('keyword');

                $query->where(function ($query) use ($keyword) {
                    $query->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('gender') && $request->input('gender') !== '0', function ($query) use ($request) {
                $query->where('gender', $request->input('gender'));
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $query->whereDate('created_at', $request->input('date'));
            })
            ->latest()
            ->paginate(7);

        $categories = Category::all();
        $tags = Tag::all();

        return view('admin.index', compact('contacts', 'categories', 'tags'));
    }

    /**
     * お問い合わせ詳細を表示する
     */
    public function show(Contact $contact): View
    {
        $contact->load(['category', 'tags']);

        return view('admin.show', compact('contact'));
    }

    /**
     * お問い合わせを削除する
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect('/admin');
    }
}
