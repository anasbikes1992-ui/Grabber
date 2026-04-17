<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TaxiCorporateAccount;
use App\Models\TaxiCorporateEmployee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxiCorporateController extends Controller
{
    /**
     * GET /v1/taxi/corporate
     */
    public function index(): JsonResponse
    {
        $accounts = TaxiCorporateAccount::withCount('employees')->latest()->paginate(20);
        return response()->json(['success' => true, 'data' => $accounts]);
    }

    /**
     * POST /v1/taxi/corporate
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
        ]);

        $account = TaxiCorporateAccount::create([
            ...$data,
            'balance'    => 0,
            'is_active'  => true,
        ]);

        return response()->json(['success' => true, 'account' => $account], 201);
    }

    /**
     * GET /v1/taxi/corporate/{id}
     */
    public function show(string $id): JsonResponse
    {
        $account = TaxiCorporateAccount::with('employees.user.profile')->findOrFail($id);
        return response()->json(['success' => true, 'account' => $account]);
    }

    /**
     * PUT /v1/taxi/corporate/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['sometimes', 'string', 'max:255'],
            'contact_name' => ['sometimes', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'email'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
            'is_active'    => ['sometimes', 'boolean'],
        ]);

        $account = TaxiCorporateAccount::findOrFail($id);
        $account->update($data);

        return response()->json(['success' => true, 'account' => $account->fresh()]);
    }

    /**
     * POST /v1/taxi/corporate/{id}/employees
     */
    public function addEmployee(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $account = TaxiCorporateAccount::findOrFail($id);

        $exists = TaxiCorporateEmployee::where('corporate_account_id', $id)
            ->where('user_id', $data['user_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'User is already an employee of this account.'], 422);
        }

        $employee = TaxiCorporateEmployee::create([
            'corporate_account_id' => $id,
            'user_id'              => $data['user_id'],
        ]);

        return response()->json(['success' => true, 'employee' => $employee], 201);
    }

    /**
     * DELETE /v1/taxi/corporate/{id}/employees/{userId}
     */
    public function removeEmployee(string $id, string $userId): JsonResponse
    {
        $deleted = TaxiCorporateEmployee::where('corporate_account_id', $id)
            ->where('user_id', $userId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Employee removed.']);
    }

    /**
     * Check if a user is an employee and the account has sufficient credit.
     */
    public function checkCredit(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'user_id'        => ['required', 'uuid', 'exists:users,id'],
            'estimated_fare' => ['required', 'numeric', 'min:0'],
        ]);

        $account = TaxiCorporateAccount::findOrFail($id);

        $isEmployee = TaxiCorporateEmployee::where('corporate_account_id', $id)
            ->where('user_id', $data['user_id'])
            ->exists();

        if (!$isEmployee) {
            return response()->json(['eligible' => false, 'message' => 'User is not an employee.'], 422);
        }

        if ($account->remainingCredit() < $data['estimated_fare']) {
            return response()->json(['eligible' => false, 'message' => 'Insufficient corporate credit.'], 422);
        }

        return response()->json(['eligible' => true, 'remaining_credit' => $account->remainingCredit()]);
    }
}
