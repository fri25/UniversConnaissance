<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Téléversement d'une image insérée dans une description (éditeur de l'admin).
 */
class EditorImageController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ], [], ['image' => 'image']);

        $path = $request->file('image')->store('descriptions', 'public');

        return response()->json(['url' => Storage::disk('public')->url($path)]);
    }
}
