<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    /**
     * お問い合わせフォーム入力ページを表示する
     */
    public function index(): View
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    /**
     * 入力内容を検証し、確認ページを表示する
     */
    public function confirm(StoreContactRequest $request): View
    {
        $validated = $request->validated();
        $category = Category::findOrFail($validated['category_id']);
        $tags = Tag::whereIn('id', $validated['tag_ids'] ?? [])->get();

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    /**
     * お問い合わせを保存する
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $contact = Contact::create(Arr::except($validated, ['tag_ids']));

        if (! empty($validated['tag_ids'])) {
            $contact->tags()->attach($validated['tag_ids']);
        }

        return redirect('/thanks');
    }

    /**
     * サンクスページを表示する
     */
    public function thanks(): View
    {
        return view('contact.thanks');
    }

    /**
     * 検索条件に一致するお問い合わせをCSVで出力する
     */
    public function export(ExportContactRequest $request): StreamedResponse
    {
        $contacts = Contact::with('category')
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
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contacts.csv"',
        ];

        return response()->stream(function () use ($contacts) {
            $stream = fopen('php://output', 'w');

            // Excelで文字化けしないようにBOMを付与する
            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, ['ID', '氏名', '性別', 'メールアドレス', '電話番号', '住所', '建物名', 'お問い合わせの種類', 'お問い合わせ内容', '作成日時']);

            foreach ($contacts as $contact) {
                fputcsv($stream, [
                    $contact->id,
                    $contact->first_name.' '.$contact->last_name,
                    $contact->gender_label,
                    $contact->email,
                    $contact->tel,
                    $contact->address,
                    $contact->building,
                    $contact->category->content ?? '',
                    $contact->detail,
                    $contact->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($stream);
        }, 200, $headers);
    }
}
