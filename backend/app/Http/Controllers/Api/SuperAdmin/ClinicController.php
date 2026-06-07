<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreClinicRequest;
use App\Http\Requests\SuperAdmin\ToggleClinicStatusRequest;
use App\Http\Requests\SuperAdmin\UpdateClinicRequest;
use App\Http\Resources\ClinicResource;
use App\Models\Clinic;
use App\Services\AuditLogger;
use App\Services\SuperAdmin\ClinicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    public function index(Request $request, ClinicService $service)
    {
        $this->authorize('viewAny', Clinic::class);

        return ClinicResource::collection(
            $service->paginate(
                search: $request->string('search')->toString(),
                perPage: $request->integer('per_page', 10),
            )
        );
    }

    public function store(StoreClinicRequest $request, ClinicService $service, AuditLogger $audit): JsonResponse
    {
        $this->authorize('create', Clinic::class);

        $clinic = $service->create($request->validated());
        $audit->log($request, 'clinic.created', $clinic, ['active' => $clinic->active]);

        return response()->json((new ClinicResource($clinic))->resolve(), 201);
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic, ClinicService $service, AuditLogger $audit): JsonResponse
    {
        $this->authorize('update', $clinic);

        $updatedClinic = $service->update($clinic, $request->validated());
        $audit->log($request, 'clinic.updated', $updatedClinic, [
            'changed_fields' => array_keys($updatedClinic->getChanges()),
        ]);

        return response()->json((new ClinicResource($updatedClinic))->resolve());
    }

    public function activate(ToggleClinicStatusRequest $request, Clinic $clinic, ClinicService $service, AuditLogger $audit): JsonResponse
    {
        $this->authorize('activate', $clinic);

        $updatedClinic = $service->activate($clinic);
        $audit->log($request, 'clinic.activated', $updatedClinic, ['active' => true]);

        return response()->json((new ClinicResource($updatedClinic))->resolve());
    }

    public function deactivate(ToggleClinicStatusRequest $request, Clinic $clinic, ClinicService $service, AuditLogger $audit): JsonResponse
    {
        $this->authorize('deactivate', $clinic);

        $updatedClinic = $service->deactivate($clinic);
        $audit->log($request, 'clinic.deactivated', $updatedClinic, ['active' => false]);

        return response()->json((new ClinicResource($updatedClinic))->resolve());
    }
}
