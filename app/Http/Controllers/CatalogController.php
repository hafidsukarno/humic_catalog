<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Catalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CatalogController extends Controller
{
    public function __construct()
    {
        // Hanya admin yang login bisa akses semua method di controller ini
        $this->middleware('auth:sanctum');
    }

    // create catalog
    public function store(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255|unique:catalogs,nama',
            'deskripsi' => 'required|string|max:1000',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,docx,mp4|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->file('file')->store('catalogs', 'public');

            $catalog = Catalog::create([
                'nama' => $request->nama,
                'deskripsi' => $request->deskripsi,
                'file_link' => $path,
                'admin_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Catalog berhasil dibuat',
                'data' => $catalog
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan catalog',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // read catalog
    public function index()
    {
        try {
            $catalogs = Catalog::all();

            return response()->json([
                'success' => true,
                'data' => $catalogs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data catalog',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // read detail catalog
    public function show($id)
    {
        try {
            $catalog = Catalog::find($id);

            if (!$catalog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catalog tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $catalog
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data catalog',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // update catalog
    public function update(Request $request, $id)
    {
        // Cari catalog
        $catalog = Catalog::find($id);

        if (!$catalog) {
            return response()->json([
                'success' => false,
                'message' => 'Catalog tidak ditemukan'
            ], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255|unique:catalogs,nama,' . $id,
            'deskripsi' => 'required|string|max:1000',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx,mp4|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update nama & deskripsi
            $catalog->nama = $request->nama;
            $catalog->deskripsi = $request->deskripsi;

            // Update file jika ada
            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('catalogs', 'public');
                $catalog->file_link = $path;
            }

            // Simpan perubahan
            $catalog->save();

            // Kembalikan data terbaru
            return response()->json([
                'success' => true,
                'message' => 'Catalog berhasil diperbarui',
                'data' => $catalog->fresh() // pastikan selalu ambil data terbaru
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui catalog',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // Delete catalog
    public function destroy($id)
    {
        $catalog = Catalog::find($id);

        if (!$catalog) {
            return response()->json([
                'success' => false,
                'message' => 'Catalog tidak ditemukan'
            ], 404);
        }

        try {
            $catalog->delete();

            return response()->json([
                'success' => true,
                'message' => 'Catalog berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus catalog',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
