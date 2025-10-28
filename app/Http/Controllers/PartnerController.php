<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Partner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PartnerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['publicIndex']);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $imagePath = $request->file('image')->store('partners', 'public');

            $partner = Partner::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'image_path' => $imagePath,
                'admin_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Partner berhasil ditambahkan',
                'data' => $partner
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function publicIndex()
    {
        $partners = Partner::select( 'image_path')->get();

        if ($partners->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada partner yang ditambahkan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $partners
        ]);
    }


    public function index()
    {
        $partners = Partner::select('name', 'image_path')->get();
        return response()->json(['success' => true, 'data' => $partners]);
    }

    /**
     * READ by slug
     */
    // public function show($slug)
    // {
    //     $partner = Partner::where('slug', $slug)->first();

    //     if (!$partner) {
    //         return response()->json(['success' => false, 'message' => 'Partner tidak ditemukan'], 404);
    //     }

    //     return response()->json(['success' => true, 'data' => $partner]);
    // }

    public function update(Request $request, $slug)
    {
        $partner = Partner::where('slug', $slug)->first();

        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            if ($request->hasFile('image')) {
                Storage::disk('public')->delete($partner->image_path);
                $partner->image_path = $request->file('image')->store('partners', 'public');
            }

            $partner->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'image_path' => $partner->image_path,
            ]);

            return response()->json(['success' => true, 'message' => 'Partner berhasil diperbarui', 'data' => $partner]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function destroy($slug)
    {
        $partner = Partner::where('slug', $slug)->first();

        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner tidak ditemukan'], 404);
        }

        try {
            Storage::disk('public')->delete($partner->image_path);
            $partner->delete();

            return response()->json(['success' => true, 'message' => 'Partner berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
