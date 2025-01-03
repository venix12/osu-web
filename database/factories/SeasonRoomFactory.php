<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SeasonRoom;

class SeasonRoomFactory extends Factory
{
    protected $model = SeasonRoom::class;

    public function definition(): array
    {
        // pivot table...
        return [];
    }
}