<?php

namespace App\Actions;

use App\Models\Retro;
use App\Models\User;
use App\Support\RetroTemplates;

class CreateRetro
{
    /** @param array{title: string, template: string, is_anonymous: bool, votes_per_user: int} $data */
    public function __invoke(User $owner, array $data): Retro
    {
        $retro = Retro::create([
            'user_id'        => $owner->id,
            'title'          => $data['title'],
            'is_anonymous'   => $data['is_anonymous'],
            'votes_per_user' => $data['votes_per_user'],
        ]);

        foreach (RetroTemplates::columns($data['template']) as $position => $column) {
            $retro->columns()->create([
                'title'    => $column['title'],
                'color'    => $column['color'],
                'position' => $position,
            ]);
        }

        $retro->participants()->attach($owner->id, ['role' => Retro::ROLE_FACILITATOR]);

        return $retro;
    }
}
