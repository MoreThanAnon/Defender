<?php

namespace BusinessCore\Http\Controllers;

use BusinessCore\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Company records. Files live on a private disk and are only served through here. */
class DocumentController extends Controller
{
    public function index()
    {
        $this->allow('view');

        return $this->view('documents.index', ['documents' => Document::with(['uploader', 'attached'])->latest()->get()]);
    }

    public function store(Request $request)
    {
        $this->allow('documents');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'kind' => ['required', Rule::in(array_keys(config('business-core.document_kinds')))],
            'file' => ['required', 'file', 'max:'.config('business-core.upload_max_kb'), 'mimes:'.config('business-core.document_mimes')],
            'attach_to' => ['nullable', Rule::in(array_keys(Document::ATTACHABLE))],
            'attach_id' => ['nullable', 'integer', 'required_with:attach_to'],
        ]);

        $attachedType = null;
        if (! empty($data['attach_to'])) {
            $attachedType = Document::ATTACHABLE[$data['attach_to']];
            // Goes through the tenant scope, so another business's record is "not found".
            $attachedType::findOrFail($data['attach_id']);
        }

        $file = $request->file('file');
        $path = $file->store("business-core/{$this->biz()->id}/documents", config('business-core.disk'));

        $document = Document::create([
            'title' => $data['title'],
            'kind' => $data['kind'],
            'path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 191),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'attached_type' => $attachedType,
            'attached_id' => $attachedType ? $data['attach_id'] : null,
            'uploaded_by' => $request->user()->getKey(),
        ]);
        $this->audit('document.uploaded', $document, ['title' => $document->title, 'sha256' => $document->sha256]);

        return back()->with('status', 'Document uploaded.');
    }

    public function download(Document $document)
    {
        $this->allow('view');
        $this->audit('document.downloaded', $document);

        return Storage::disk(config('business-core.disk'))->download($document->path, $document->original_name);
    }
}
