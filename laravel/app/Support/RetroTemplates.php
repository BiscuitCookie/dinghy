<?php

namespace App\Support;

class RetroTemplates
{
    public const TEMPLATES = [
        'classic' => [
            'label'   => 'Классика: Хорошо / Плохо / Действия',
            'columns' => [
                ['title' => 'Хорошо', 'color' => 'emerald'],
                ['title' => 'Плохо', 'color' => 'rose'],
                ['title' => 'Действия', 'color' => 'sky'],
            ],
        ],
        'start_stop_continue' => [
            'label'   => 'Начать / Продолжить / Прекратить',
            'columns' => [
                ['title' => 'Начать', 'color' => 'sky'],
                ['title' => 'Продолжить', 'color' => 'emerald'],
                ['title' => 'Прекратить', 'color' => 'rose'],
            ],
        ],
        'mad_sad_glad' => [
            'label'   => 'Mad / Sad / Glad',
            'columns' => [
                ['title' => 'Бесит', 'color' => 'rose'],
                ['title' => 'Грустно', 'color' => 'amber'],
                ['title' => 'Радует', 'color' => 'emerald'],
            ],
        ],
    ];

    /** @return array<int, array{title: string, color: string}> */
    public static function columns(string $key): array
    {
        return self::TEMPLATES[$key]['columns'] ?? self::TEMPLATES['classic']['columns'];
    }
}
