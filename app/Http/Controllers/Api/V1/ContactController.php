<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class ContactController extends Controller
{
    /**
     * お問い合わせ一覧を取得する
     */
    public function index(IndexContactRequest $request): AnonymousResourceCollection
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
            ->paginate(10);

        return ContactResource::collection($contacts);
    }

    /**
     * お問い合わせ詳細を取得する
     */
    public function show(Contact $contact): ContactResource
    {
        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
    }

    /**
     * お問い合わせを作成する
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $contact = Contact::create(Arr::except($validated, ['tag_ids']));

        if (! empty($validated['tag_ids'])) {
            $contact->tags()->attach($validated['tag_ids']);
        }

        $contact->load(['category', 'tags']);

        return (new ContactResource($contact))->response()->setStatusCode(201);
    }

    /**
     * お問い合わせを更新する
     */
    public function update(UpdateContactRequest $request, Contact $contact): ContactResource
    {
        $validated = $request->validated();
        $contact->update(Arr::except($validated, ['tag_ids']));
        $contact->tags()->sync($validated['tag_ids'] ?? []);
        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
    }

    /**
     * お問い合わせを削除する
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $contact->delete();

        return response()->json(null, 204);
    }
}
