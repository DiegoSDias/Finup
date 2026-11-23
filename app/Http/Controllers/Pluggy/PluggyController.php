<?php

namespace App\Http\Controllers\Pluggy;

use App\Http\Controllers\Controller;
use App\Services\Pluggy\PluggyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PluggyController extends Controller
{
    public function connectToken(Request $request, PluggyService $pluggyService): JsonResponse
    {
        $user = Auth::id();

        try {
            $accessToken = $pluggyService->createConnectToken(
                (string) $user,
                avoidDuplicates: ! $request->boolean('forceNew'),
            );
        } catch (RequestException $exception) {
            Log::warning('Não foi possível criar o Connect Token da Pluggy.', [
                'user_id' => $user,
                'status' => $exception->response?->status(),
            ]);

            return response()->json([
                'message' => 'Não foi possível iniciar a conexão bancária. Tente novamente em instantes.',
            ], 502);
        } catch (Throwable $exception) {
            Log::error('Falha ao criar o Connect Token da Pluggy.', [
                'user_id' => $user,
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'A conexão bancária não está disponível no momento.',
            ], 500);
        }

        return response()->json([
            'accessToken' => $accessToken,
        ]);
    }
}
