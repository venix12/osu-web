<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace Tests\Controllers;

use App\Models\Beatmap;
use App\Models\BeatmapTag;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class BeatmapTagsControllerTest extends TestCase
{
    private Tag $tag;
    private Beatmap $beatmap;
    private BeatmapTag $beatmapTag;

    public function testIndex(): void
    {
        $this->actAsScopedUser(User::factory()->create(), ['public']);

        $this
            ->get(route('api.beatmaps.tags.index', ['beatmap' => $this->beatmap->getKey()]))
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->where('beatmap_tags.0.tag_id', $this->tag->getKey())
                    ->where('beatmap_tags.0.name', $this->tag->name)
                    ->where('beatmap_tags.0.tag_count', 1)
                    ->etc());
    }

    public function testStore(): void
    {
        $this->expectCountChange(fn () => BeatmapTag::count(), 1);

        $this->actAsScopedUser(User::factory()->create(), ['*']);
        $this
            ->post(route('api.beatmaps.tags.store', ['beatmap' => $this->beatmap->getKey()]), ['tag_id' => $this->tag->getKey()])
            ->assertSuccessful();
    }

    public function testDestroy(): void
    {
        $this->expectCountChange(fn () => BeatmapTag::count(), -1);

        $this->actAsScopedUser($this->beatmapTag->user, ['*']);
        $this
            ->delete(route('api.beatmaps.tags.destroy', ['beatmap' => $this->beatmap->getKey()]), ['tag_id' => $this->tag->getKey()])
            ->assertSuccessful();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tag = Tag::factory()->create();
        $this->beatmap = Beatmap::factory()->create();
        $this->beatmapTag = BeatmapTag::factory()->create([
            'tag_id' => $this->tag->getKey(),
            'beatmap_id' => $this->beatmap->getKey(),
        ]);
    }
}
