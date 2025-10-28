<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['publicTitle', 'publicIndex', 'publicShow']);
    }

    public function publicTitle()
    {
        $products = Product::select('title')->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }


    public function publicIndex(Request $request)
    {
        try {
            $category = $request->query('category');
            $title = $request->query('title');

            if ($title) {
                $product = Product::where('title', $title)
                    ->select('description')
                    ->first();

                if (!$product) {
                    return response()->json(['success' => false, 'message' => 'Title tidak ditemukan'], 404);
                }

                return response()->json(['success' => true, 'data' => $product]);
            }

            $query = Product::select('title', 'subtitle', 'thumbnail_path', 'category');

            if ($category) {
                $query->where('category', $category);
            }

            $products = $query->get();

            if ($products->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'catalog tidak ditemukan'], 404);
            }

            return response()->json(['success' => true, 'data' => $products]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function publicShow($slug)
    {
        try {
            $product = Product::where('slug', $slug)
                ->select('thumbnail_path', 'title', 'subtitle', 'category', 'description', 'file_path', 'created_at')
                ->first();

            if (!$product) {
                return response()->json(['success' => false, 'message' => 'catalog tidak ditemukan'], 404);
            }

            return response()->json(['success' => true, 'data' => $product]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $products = Product::select('title', 'subtitle', 'thumbnail_path', 'category')->get();

        return response()->json(['success' => true, 'data' => $products]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'subtitle' => 'nullable|string|max:255',
            'category' => 'required|string',
            'description' => 'nullable|string|max:2000',
            'thumbnail' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:20480',
            'file_path' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // === Simpan thumbnail ===
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
            $finalPath = null;

            $hasFile = $request->hasFile('file');
            $hasUrl = !empty($request->file_path);

            // === Validasi agar hanya salah satu yang diisi ===
            if ($hasFile && $hasUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya boleh salah satu: upload file ATAU isi URL file_path, bukan keduanya.',
                ], 422);
            }

            if (!$hasFile && !$hasUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Harus mengisi salah satu: upload file ATAU isi URL file_path.',
                ], 422);
            }

            // === Jika upload file ===
            if ($hasFile) {
                $uploadedPath = $request->file('file')->store('uploads', 'public');
                $fileExt = strtolower($request->file('file')->getClientOriginalExtension());
                $finalPath = $uploadedPath;

                // Ubah ke PDF jika bukan PDF
                if ($fileExt !== 'pdf') {
                    $pdfName = pathinfo($uploadedPath, PATHINFO_FILENAME) . '.pdf';
                    $pdfPath = 'uploads/' . $pdfName;

                    $html = "
                        <h1>{$request->title}</h1>
                        <h3>{$request->subtitle}</h3>
                        <p><strong>Kategori:</strong> {$request->category}</p>
                        <p>{$request->description}</p>
                    ";

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                    \Illuminate\Support\Facades\Storage::disk('public')->put($pdfPath, $pdf->output());
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($uploadedPath);

                    $finalPath = $pdfPath;
                }
            }

            // === Jika isi URL ===
            if ($hasUrl) {
                $finalPath = $request->file_path;
            }

            $slug = \Illuminate\Support\Str::slug($request->title);

            $product = \App\Models\Product::create([
                'title' => $request->title,
                'slug' => $slug,
                'subtitle' => $request->subtitle,
                'category' => $request->category,
                'description' => $request->description,
                'thumbnail_path' => $thumbnailPath,
                'file_path' => $finalPath,
                'admin_id' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'catalog berhasil dibuat',
                'data' => $product,
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) { // 1062 = Duplicate entry MySQL
                return response()->json([
                    'success' => false,
                    'message' => 'catalog dengan judul ini sudah ada.',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan catalog.',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->select('title', 'subtitle', 'category', 'description', 'file_path', 'thumbnail_path', 'created_at')
            ->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'catalog tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $product]);
    }

    public function update(Request $request, $slug)
    {
        $product = Product::where('slug', $slug)->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:products,title,' . $product->id,
            'subtitle' => 'nullable|string|max:255',
            'category' => 'required',
            'description' => 'nullable|string|max:2000',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:20480',
            'file_path' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // === Update thumbnail jika ada ===
            if ($request->hasFile('thumbnail')) {
                if ($product->thumbnail_path) {
                    Storage::disk('public')->delete($product->thumbnail_path);
                }
                $product->thumbnail_path = $request->file('thumbnail')->store('thumbnails', 'public');
            }

            $hasFile = $request->hasFile('file');
            $hasUrl = !empty($request->file_path);
            $finalPath = $product->file_path; // default: tetap pakai lama

            // === Validasi agar hanya salah satu yang diisi ===
            if ($hasFile && $hasUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya boleh salah satu: upload file ATAU isi URL file_path, bukan keduanya.',
                ], 422);
            }

            if (!$hasFile && !$hasUrl && !$product->file_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Harus mengisi salah satu: upload file ATAU isi URL file_path.',
                ], 422);
            }

            // === Jika upload file baru ===
            if ($hasFile) {
                // hapus file lama (kalau lokal)
                if ($product->file_path && !Str::startsWith($product->file_path, ['http://', 'https://'])) {
                    Storage::disk('public')->delete($product->file_path);
                }

                $uploadedPath = $request->file('file')->store('uploads', 'public');
                $fileExt = strtolower($request->file('file')->getClientOriginalExtension());
                $finalPath = $uploadedPath;

                // ubah ke PDF jika bukan PDF
                if ($fileExt !== 'pdf') {
                    $pdfName = pathinfo($uploadedPath, PATHINFO_FILENAME) . '.pdf';
                    $pdfPath = 'uploads/' . $pdfName;

                    $html = "
                        <h1>{$request->title}</h1>
                        <h3>{$request->subtitle}</h3>
                        <p><strong>Kategori:</strong> {$request->category}</p>
                        <p>{$request->description}</p>
                    ";

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                    Storage::disk('public')->put($pdfPath, $pdf->output());
                    Storage::disk('public')->delete($uploadedPath);

                    $finalPath = $pdfPath;
                }
            }

            // === Jika isi URL baru ===
            if ($hasUrl) {
                // hapus file lama (kalau lokal)
                if ($product->file_path && !Str::startsWith($product->file_path, ['http://', 'https://'])) {
                    Storage::disk('public')->delete($product->file_path);
                }

                $finalPath = $request->file_path;
            }

            // === Buat slug baru ===
            $newSlug = Str::slug($request->title);

            // === Update ke database ===
            $product->update([
                'title' => $request->title,
                'slug' => $newSlug,
                'subtitle' => $request->subtitle,
                'category' => $request->category,
                'description' => $request->description,
                'thumbnail_path' => $product->thumbnail_path,
                'file_path' => $finalPath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil diperbarui.',
                'data' => $product,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk dengan judul ini sudah ada.',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui produk.',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy($slug)
    {
        $product = Product::where('slug', $slug)->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'catalog tidak ditemukan'], 404);
        }

        try {
            Storage::disk('public')->delete([$product->thumbnail_path, $product->file_path]);
            $product->delete();

            return response()->json(['success' => true, 'message' => 'catalog berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
