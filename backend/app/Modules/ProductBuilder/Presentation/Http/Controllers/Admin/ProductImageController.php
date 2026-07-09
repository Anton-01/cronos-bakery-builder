<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin;

use App\Modules\ProductBuilder\Domain\Models\Product;
use App\Modules\ProductBuilder\Domain\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductImageController extends Controller
{
    public function store(Request $request, int $product): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120'],
            'field' => ['required', 'in:image,gallery'],
        ]);

        $productModel = Product::query()->findOr($product, fn () => throw new NotFoundHttpException('Product not found.'));

        $path = $request->file('file')->store('products', 'public');
        $url = Storage::disk('public')->url($path);

        if ($request->input('field') === 'image') {
            $productModel->update(['image' => $url]);
        } else {
            $position = (int) $productModel->gallery()->max('position') + 1;
            $productModel->gallery()->create([
                'path' => $url,
                'name' => pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME),
                'alt_text' => null,
                'position' => $position,
            ]);
        }

        $productModel->load(['gallery' => fn ($q) => $q->orderBy('position')]);

        return response()->json([
            'data' => [
                'id' => $productModel->id,
                'image' => $productModel->image,
                'gallery' => $productModel->gallery->map(fn (ProductImage $img) => [
                    'id' => $img->id,
                    'path' => $img->path,
                    'name' => $img->name,
                    'alt_text' => $img->alt_text,
                    'position' => $img->position,
                ]),
            ],
        ]);
    }

    public function update(Request $request, int $product, int $image): JsonResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $img = ProductImage::query()
            ->where('product_id', $product)
            ->findOr($image, fn () => throw new NotFoundHttpException('Image not found.'));

        $img->update($request->only(['name', 'alt_text']));

        return response()->json(['data' => $img->refresh()]);
    }

    public function destroy(int $product, int $image): JsonResponse
    {
        $img = ProductImage::query()
            ->where('product_id', $product)
            ->findOr($image, fn () => throw new NotFoundHttpException('Image not found.'));

        $img->delete();

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
