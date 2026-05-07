<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Listar reseñas de un club o cancha.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'reviewable_type' => 'required|in:club,court',
            'reviewable_id' => 'required|integer',
        ]);

        $type = $request->reviewable_type === 'club'
            ? 'App\Models\Club'
            : 'App\Models\Court';

        $reviews = Review::with('user')
            ->where('reviewable_type', $type)
            ->where('reviewable_id', $request->reviewable_id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Crear una reseña.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reviewable_type' => 'required|in:club,court',
            'reviewable_id' => 'required|integer',
            'booking_id' => 'nullable|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['reviewable_type'] = $validated['reviewable_type'] === 'club'
            ? 'App\Models\Club'
            : 'App\Models\Court';

        $review = Review::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reseña creada exitosamente.',
            'data' => new ReviewResource($review),
        ], 201);
    }

    /**
     * Actualizar una reseña.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $review = Review::where('user_id', auth()->id())
            ->findOrFail($id);

        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reseña actualizada.',
            'data' => new ReviewResource($review->fresh()),
        ]);
    }

    /**
     * Eliminar una reseña.
     */
    public function destroy(int $id): JsonResponse
    {
        $review = Review::where('user_id', auth()->id())
            ->findOrFail($id);

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reseña eliminada.',
        ]);
    }
}
