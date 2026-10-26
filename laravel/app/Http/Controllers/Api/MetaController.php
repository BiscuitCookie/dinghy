<?php

namespace App\Http\Controllers\Api;

use App\Enums\RetroPhase;
use App\Http\Controllers\Controller;
use App\Support\RetroTemplates;
use Illuminate\Http\JsonResponse;

class MetaController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'phases' => array_map(
                fn (RetroPhase $phase, int $index) => [
                    'value' => $phase->value,
                    'label' => $phase->label(),
                    'index' => $index,
                ],
                RetroPhase::flow(),
                array_keys(RetroPhase::flow()),
            ),
            'templates' => array_map(
                fn (string $key, array $template) => ['key' => $key, 'label' => $template['label']],
                array_keys(RetroTemplates::TEMPLATES),
                RetroTemplates::TEMPLATES,
            ),
        ]);
    }
}
