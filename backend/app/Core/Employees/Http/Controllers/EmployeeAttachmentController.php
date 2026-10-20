<?php

namespace App\Core\Employees\Http\Controllers;

use App\Core\Employees\Models\Employee;
use App\Core\Files\FileStorage;
use App\Core\Files\Http\Resources\AttachmentResource;
use App\Core\Files\Models\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documentos anexos à ficha do colaborador (RH gere; o próprio pode consultar).
 */
class EmployeeAttachmentController
{
    public function __construct(private readonly FileStorage $files) {}

    public function index(Employee $employee): AnonymousResourceCollection
    {
        Gate::authorize('view', $employee);
        abort_unless($this->canSee($employee), 403);

        return AttachmentResource::collection($employee->attachments()->with('uploader')->latest()->get());
    }

    public function store(Request $request, Employee $employee): JsonResponse
    {
        Gate::authorize('update', $employee);
        $request->validate(['file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt']]);

        $attachment = $this->files->attach($employee, $request->file('file'), $request->user()->id);

        return (new AttachmentResource($attachment->load('uploader')))->response()->setStatusCode(201);
    }

    public function download(Employee $employee, Attachment $attachment): StreamedResponse
    {
        $this->assertBelongs($employee, $attachment);
        Gate::authorize('view', $employee);
        abort_unless($this->canSee($employee), 403);

        return $this->files->download($attachment);
    }

    public function destroy(Employee $employee, Attachment $attachment): Response
    {
        $this->assertBelongs($employee, $attachment);
        Gate::authorize('update', $employee);
        $this->files->delete($attachment);

        return response()->noContent();
    }

    private function canSee(Employee $employee): bool
    {
        $user = request()->user();

        return $user->can('core.employees.manage') || $employee->user_id === $user->id;
    }

    private function assertBelongs(Employee $employee, Attachment $attachment): void
    {
        abort_unless($attachment->attachable_type === $employee->getMorphClass() && (int) $attachment->attachable_id === $employee->id, 404);
    }
}
