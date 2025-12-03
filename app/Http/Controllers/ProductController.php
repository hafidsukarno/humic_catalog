<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use App\Models\StatusLog;




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

            $query = Product::select('title', 'thumbnail_path');

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
                ->select('thumbnail_path', 'title', 'category', 'description', 'file_path', 'user_manual', 'file_url')
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
        $products = Product::select('title', 'thumbnail_path', 'category')->get();

        return response()->json(['success' => true, 'data' => $products]);
    }

    #admin
    #internship
    public function storeInternship(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:2000',
            'user_manual'  => 'nullable|url|max:255',
            'file'         => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:20480',
            'file_url'     => 'nullable|url|max:255',
            'thumbnail'    => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {

            $webpPath = null;

            if ($request->hasFile('thumbnail')) {

                $thumbnail = $request->file('thumbnail');

                $image = Image::read($thumbnail->getRealPath())->toWebp(80);

                $webpPath = 'products/' . Str::random(20) . '.webp';
                Storage::disk('public')->put($webpPath, (string) $image);
            }

            $finalFilePath = null;

            if ($request->hasFile('file')) {

                $uploadedPath = $request->file('file')->store('uploads', 'public');
                $extension = strtolower($request->file('file')->getClientOriginalExtension());

                if ($extension !== 'pdf') {

                    $pdfName = pathinfo($uploadedPath, PATHINFO_FILENAME) . '.pdf';
                    $pdfPath = 'uploads/' . $pdfName;

                    $html = "
                        <h1>{$request->title}</h1>
                        <p>{$request->description}</p>
                    ";

                    $pdf = Pdf::loadHTML($html);

                    Storage::disk('public')->put($pdfPath, $pdf->output());
                    Storage::disk('public')->delete($uploadedPath);

                    $finalFilePath = $pdfPath;
                } else {
                    $finalFilePath = $uploadedPath;
                }
            }

            $finalUrl = $request->file_url ?? null;

            $slug = Str::slug($request->title);


            $product = Product::create([
                'title'         => $request->title,
                'slug'          => $slug,
                'category'      => 'Internship Project',
                'description'   => $request->description,
                'user_manual'   => $request->user_manual,

                'file_path'     => $finalFilePath,
                'file_url'      => $finalUrl,

                'thumbnail_path' => $webpPath,
                'admin_id'      => Auth::id(),
            ]);

            StatusLog::create([
                'admin_id'       => Auth::id(),
                'product_id'     => $product->id,
                'partner_id'     => null,
                'product_title'  => $product->title, // <-- tambahkan snapshot title
                'action'         => 'create',
                'status_message' => 'Catalog Internship berhasil dibuat.',
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Catalog Internship berhasil dibuat.',
                'data'    => $product,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }



    public function detailInternship($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('category', 'Internship Project')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'catalog tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'thumbnail' => $product->thumbnail_path,
                'title' => $product->title,
                'description' => $product->description,
                'user_manual' => $product->user_manual,
                'file_path' => $product->file_path,
                'file_url' => $product->file_url,
            ]
        ]);
    }

    public function updateInternship(Request $request, $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('category', 'Intership Project')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Catalog tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:2000',
            'user_manual'  => 'nullable|url|max:255',
            'file'         => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:20480',
            'file_url'     => 'nullable|url|max:255',
            'thumbnail'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {

            if ($request->hasFile('thumbnail')) {
                // hapus file lama
                if ($product->thumbnail_path) {
                    Storage::disk('public')->delete($product->thumbnail_path);
                }

                $thumbnail = $request->file('thumbnail');
                $image = Image::read($thumbnail->getRealPath())->toWebp(80);

                $webpPath = 'products/' . Str::random(20) . '.webp';
                Storage::disk('public')->put($webpPath, (string) $image);

                $product->thumbnail_path = $webpPath;
            }


            if ($request->hasFile('file')) {
                // hapus file lama
                if ($product->file_path) {
                    Storage::disk('public')->delete($product->file_path);
                }

                $uploadedPath = $request->file('file')->store('uploads', 'public');
                $extension = strtolower($request->file('file')->getClientOriginalExtension());

                if ($extension !== 'pdf') {
                    $pdfName = pathinfo($uploadedPath, PATHINFO_FILENAME) . '.pdf';
                    $pdfPath = 'uploads/' . $pdfName;

                    $html = "
                        <h1>{$request->title}</h1>
                        <p>{$request->description}</p>
                    ";

                    $pdf = Pdf::loadHTML($html);
                    Storage::disk('public')->put($pdfPath, $pdf->output());
                    Storage::disk('public')->delete($uploadedPath);

                    $product->file_path = $pdfPath;
                } else {
                    $product->file_path = $uploadedPath;
                }
            }



            $product->title = $request->title;
            $product->slug = Str::slug($request->title);
            $product->description = $request->description;
            $product->user_manual = $request->user_manual;
            $product->file_url = $request->file_url ?? null;

            $product->save();

            StatusLog::create([
                'admin_id'       => Auth::id(),
                'product_id'     => $product->id,
                'partner_id'     => null,
                'product_title'  => $product->title, // <-- snapshot title saat update
                'action'         => 'update',
                'status_message' => 'Catalog Internship berhasil diperbarui.',
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Catalog Intership berhasil diperbarui.',
                'data'    => $product,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function deleteInternship($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('category', 'Internship Project')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Catalog tidak ditemukan'
            ], 404);
        }

        try {

            StatusLog::create([
                'admin_id'       => Auth::id(),
                'product_id'     => $product->id,
                'partner_id'     => null,
                'product_title'  => $product->title,
                'action'         => 'delete',
                'status_message' => 'Catalog Internship berhasil dihapus.',
            ]);

            if ($product->file_path) {
                Storage::disk('public')->delete($product->file_path);
            }

            if ($product->thumbnail_path) {
                Storage::disk('public')->delete($product->thumbnail_path);
            }


            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Catalog berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus catalog.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }




    #research
     public function storeResearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:2000',
            'user_manual'  => 'nullable|url|max:255',
            'file'         => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:20480',
            'file_url'     => 'nullable|url|max:255',
            'thumbnail'    => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {

            $webpPath = null;

            if ($request->hasFile('thumbnail')) {

                $thumbnail = $request->file('thumbnail');

                $image = Image::read($thumbnail->getRealPath())->toWebp(80);

                $webpPath = 'products/' . Str::random(20) . '.webp';
                Storage::disk('public')->put($webpPath, (string) $image);
            }


            $finalFilePath = null;

            if ($request->hasFile('file')) {

                $uploadedPath = $request->file('file')->store('uploads', 'public');
                $extension = strtolower($request->file('file')->getClientOriginalExtension());

                if ($extension !== 'pdf') {

                    $pdfName = pathinfo($uploadedPath, PATHINFO_FILENAME) . '.pdf';
                    $pdfPath = 'uploads/' . $pdfName;

                    $html = "
                        <h1>{$request->title}</h1>
                        <p>{$request->description}</p>
                    ";

                    $pdf = Pdf::loadHTML($html);

                    Storage::disk('public')->put($pdfPath, $pdf->output());
                    Storage::disk('public')->delete($uploadedPath);

                    $finalFilePath = $pdfPath;
                } else {
                    $finalFilePath = $uploadedPath;
                }
            }

            $finalUrl = $request->file_url ?? null;

            $slug = Str::slug($request->title);

            $product = Product::create([
                'title'         => $request->title,
                'slug'          => $slug,
                'category'      => 'Research Project',
                'description'   => $request->description,
                'user_manual'   => $request->user_manual,

                'file_path'     => $finalFilePath,
                'file_url'      => $finalUrl,

                'thumbnail_path' => $webpPath,
                'admin_id'      => Auth::id(),
            ]);


            StatusLog::create([
                'admin_id'       => Auth::id(),
                'product_id'     => $product->id,
                'partner_id'     => null,
                'product_title'  => $product->title,
                'action'         => 'create',
                'status_message' => 'Catalog Research berhasil dibuat.',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Catalog Research berhasil dibuat.',
                'data'    => $product,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function detailResearch($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('category', 'Research Project')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'catalog tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'thumbnail' => $product->thumbnail_path,
                'title' => $product->title,
                'subtitle' => $product->subtitle,
                'description' => $product->description,
                'user_manual' => $product->user_manual,
                'file_path' => $product->file_path,
                'file_url' => $product->file_url,
            ]
        ]);
    }

    public function updateResearch(Request $request, $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('category', 'Research Project')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Catalog tidak ditemukan'
            ], 404);
        }


        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:2000',
            'user_manual'  => 'nullable|url|max:255',
            'file'         => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:20480',
            'file_url'     => 'nullable|url|max:255',
            'thumbnail'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {

            if ($request->hasFile('thumbnail')) {
                // hapus file lama
                if ($product->thumbnail_path) {
                    Storage::disk('public')->delete($product->thumbnail_path);
                }

                $thumbnail = $request->file('thumbnail');
                $image = Image::read($thumbnail->getRealPath())->toWebp(80);

                $webpPath = 'products/' . Str::random(20) . '.webp';
                Storage::disk('public')->put($webpPath, (string) $image);

                $product->thumbnail_path = $webpPath;
            }

            if ($request->hasFile('file')) {
                // hapus file lama
                if ($product->file_path) {
                    Storage::disk('public')->delete($product->file_path);
                }

                $uploadedPath = $request->file('file')->store('uploads', 'public');
                $extension = strtolower($request->file('file')->getClientOriginalExtension());

                if ($extension !== 'pdf') {
                    $pdfName = pathinfo($uploadedPath, PATHINFO_FILENAME) . '.pdf';
                    $pdfPath = 'uploads/' . $pdfName;

                    $html = "
                        <h1>{$request->title}</h1>
                        <p>{$request->description}</p>
                    ";

                    $pdf = Pdf::loadHTML($html);
                    Storage::disk('public')->put($pdfPath, $pdf->output());
                    Storage::disk('public')->delete($uploadedPath);

                    $product->file_path = $pdfPath;
                } else {
                    $product->file_path = $uploadedPath;
                }
            }


            $product->title = $request->title;
            $product->slug = Str::slug($request->title);
            $product->description = $request->description;
            $product->user_manual = $request->user_manual;
            $product->file_url = $request->file_url ?? null;

            $product->save();

            StatusLog::create([
                'admin_id'       => Auth::id(),
                'product_id'     => $product->id,
                'partner_id'     => null,
                'product_title'  => $product->title,
                'action'         => 'update',
                'status_message' => 'Catalog Research berhasil diperbarui.',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Catalog Research berhasil diperbarui.',
                'data'    => $product,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteResearch($slug)
    {
        try {
            // Cari data berdasarkan slug + category Research Project
            $product = Product::where('slug', $slug)
                ->where('category', 'Research Project')
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catalog tidak ditemukan'
                ], 404);
            }

            StatusLog::create([
                'admin_id'       => Auth::id(),
                'product_id'     => $product->id,
                'partner_id'     => null,
                'product_title'  => $product->title,
                'action'         => 'delete',
                'status_message' => 'Catalog Research Project berhasil dihapus.',
            ]);


            // Hapus thumbnail
            if ($product->thumbnail_path && Storage::disk('public')->exists($product->thumbnail_path)) {
                Storage::disk('public')->delete($product->thumbnail_path);
            }

            // Hapus file utama (hanya jika bukan URL)
            if ($product->file_path && !filter_var($product->file_path, FILTER_VALIDATE_URL)) {
                if (Storage::disk('public')->exists($product->file_path)) {
                    Storage::disk('public')->delete($product->file_path);
                }
            }


            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Catalog berhasil dihapus'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function search(Request $request, $category)
    {
        $q = $request->q ?? '';

        // Normalisasi kategori
        if ($category === 'research') {
            $categoryName = 'Research Project';
        } elseif ($category === 'internship') {
            $categoryName = 'Internship Project';
        } else {
            return response()->json([
                "success" => false,
                "message" => "Kategori tidak valid."
            ], 400);
        }

        // Query sederhana
        $data = Product::where('category', $categoryName)
            ->where('title', 'LIKE', "%{$q}%")
            ->select('id', 'thumbnail_path', 'title', 'description')
            ->get();

        return response()->json([
            "success" => true,
            "category" => $categoryName,
            "query" => $q,
            "count" => $data->count(),
            "data" => $data,
        ]);
    }

    public function getByCategory($category)
    {
        $mapping = [
            'research'   => 'Research Project',
            'internship' => 'Internship Project',
        ];

        if (!array_key_exists($category, $mapping)) {
            return response()->json([
                'message' => 'Invalid category'
            ], 400);
        }

        $products = Product::select( 'thumbnail_path', 'title', 'description')
            ->where('category', $mapping[$category])
            ->get();

        return response()->json([
            'message' => 'Success',
            'category' => $mapping[$category],
            'data' => $products
        ]);
    }

}