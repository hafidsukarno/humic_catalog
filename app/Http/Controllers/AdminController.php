<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function updateAdmin(Request $request)
    {
        $admin = Admin::first();

        // Validasi
        $request->validate([
            'name'  => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'image' => 'nullable|image|max:4096',
        ]);

        // Update name kalau dikirim
        if ($request->filled('name')) {
            $admin->name = $request->name;
        }

        // Update email kalau dikirim
        if ($request->filled('email')) {
            $admin->email = $request->email;
        }

        // Update image kalau ada file
        if ($request->hasFile('image')) {

            // Hapus gambar lama dari storage
            if ($admin->image && Storage::disk('public')->exists('admin/' . $admin->image)) {
                Storage::disk('public')->delete('admin/' . $admin->image);
            }

            // Upload & convert ke webp
            $admin->image = $this->uploadWebpToStorage($request->file('image'));
        }

        $admin->save();

        return response()->json([
            'success' => true,
            'data' => $admin
        ]);
    }

    private function uploadWebpToStorage($file)
    {
        $ext  = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        // nama webp baru
        $newName = time() . '_' . uniqid() . '.webp';

        // path storage/app/public/admin/new.webp
        $storagePath = storage_path('app/public/admin/' . $newName);

        // buat folder jika belum ada
        if (!Storage::disk('public')->exists('admin')) {
            Storage::disk('public')->makeDirectory('admin');
        }

        // jika file sudah webp → langsung simpan ke storage
        if ($ext === 'webp') {
            $file->storeAs('admin', $newName, 'public');
            return $newName;
        }

        // convert dari jpg/png ke webp
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $img = imagecreatefromjpeg($path);
                break;
            case 'png':
                $img = imagecreatefrompng($path);
                imagepalettetotruecolor($img);
                break;
            default:
                $img = imagecreatefromstring(file_get_contents($path));
                break;
        }

        // simpan ke storage dalam format webp
        imagewebp($img, $storagePath, 80);
        imagedestroy($img);

        return $newName;
    }
}
