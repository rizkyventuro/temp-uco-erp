<?php

namespace App\Http\Controllers;

use App\Models\MasterPoo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;


class ManagementPooController extends Controller
{
    public function index(Request $request)
    {
        $poos = MasterPoo::with('creator')
            ->where('created_by', Auth::id())
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('perPage', 10))
            ->through(fn($poo) => [
                'id'      => $poo->id,
                'name'    => $poo->name,
                'address' => $poo->address,
                'contact' => $poo->contact,
                'type'    => $poo->type,
            ]);

        return Inertia::render('admin/managementPoo/ListManagementPoo', [
            'poos'    => $poos,
            'filters' => $request->only(['search', 'perPage']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'required|string',
            'contact' => 'nullable|string|max:50',
            'type'    => 'required|in:Restoran,UMKM,Rumah Tangga',
        ]);

        DB::transaction(function () use ($request) {
            MasterPoo::create([
                'name'          => $request->name,
                'address'       => $request->address,
                'contact'       => $request->contact,
                'business_type' => MasterPoo::getBusinessTypeValue($request->type),
                'created_by'    => Auth::id(),
            ]);
        });


        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'required|string',
            'contact' => 'nullable|string|max:50',
            'type'    => 'required|in:Restoran,UMKM,Rumah Tangga',
        ]);

        $poo = MasterPoo::where('created_by', Auth::id())->findOrFail($id);

        DB::transaction(function () use ($poo, $request) {
            $poo->update([
                'name'          => $request->name,
                'address'       => $request->address,
                'contact'       => $request->contact,
                'business_type' => MasterPoo::getBusinessTypeValue($request->type),
            ]);
        });


        return redirect()->back();
    }

    public function destroy($id)
    {
        $poo = MasterPoo::where('created_by', Auth::id())->findOrFail($id);

        DB::transaction(function () use ($poo) {
            $poo->delete();
        });


        return redirect()->back();
    }
}
