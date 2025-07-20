<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Document::query();

        if ($request->has('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->has('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        $documents = $query->latest()->paginate();
        return $this->paginatedResponse($documents);
    }

    public function show(Document $document): JsonResponse
    {
        return $this->successResponse($document);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'document_type' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:10240', // 10MB max
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
            'is_required' => 'boolean'
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        $document = Document::create([
            'user_id' => auth()->id(),
            'document_type' => $request->document_type,
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'documentable_type' => $request->documentable_type,
            'documentable_id' => $request->documentable_id,
            'is_required' => $request->input('is_required', false),
        ]);

        return $this->successResponse($document, 'Document uploaded successfully', 201);
    }

    public function update(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'document_type' => 'sometimes|string',
            'is_required' => 'sometimes|boolean'
        ]);

        $document->update($request->only([
            'title',
            'description',
            'document_type',
            'is_required'
        ]));

        return $this->successResponse($document, 'Document updated successfully');
    }

    public function destroy(Document $document): JsonResponse
    {
        $document->delete();
        return $this->successResponse(null, 'Document deleted successfully', 204);
    }

    public function verify(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string'
        ]);

        $document->verify(auth()->user(), $request->notes);
        return $this->successResponse($document, 'Document verified successfully');
    }

    public function reject(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string'
        ]);

        $document->reject(auth()->user(), $request->reason);
        return $this->successResponse($document, 'Document rejected successfully');
    }

    public function download(Document $document): JsonResponse|StreamedResponse
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            return $this->errorResponse('File not found', 404);
        }

        return Storage::disk('public')->download(
            $document->file_path,
            $document->file_name
        );
    }
}