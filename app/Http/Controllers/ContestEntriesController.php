<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Http\Controllers;

use App\Exceptions\InvariantException;
use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\UserContestEntry;
use App\Transformers\ContestTransformer;
use Auth;
use Ds\Set;
use Illuminate\Support\Facades\DB;
use Request;

class ContestEntriesController extends Controller
{
    public function judgeResults($id)
    {
        $entry = ContestEntry::with('contest')->findOrFail($id);

        abort_if(!$entry->contest->isJudged() || !$entry->contest->show_votes, 404);

        $entry->load([
            'contest.entries',
            'contest.scoringCategories',
            'judgeVotes.scores',
            'judgeVotes.user',
            'user',
        ])->loadSum('scores', 'value');

        $contest = $entry->contest
            ->loadCount('judges')
            ->loadSum('scoringCategories', 'max_value');

        $contestJson = json_item(
            $contest,
            new ContestTransformer(),
            [
                'max_judging_score',
                'max_total_score',
                'scoring_categories',
            ],
        );

        $entryJson = json_item($entry, 'ContestEntry', [
            'judge_votes.scores',
            'judge_votes.total_score',
            'judge_votes.user',
            'results',
            'user',
        ]);

        $entriesJson = json_collection($entry->contest->entries, 'ContestEntry');

        return ext_view('contest_entries.judge-results', [
            'contestJson' => $contestJson,
            'entryJson' => $entryJson,
            'entriesJson' => $entriesJson,
        ]);
    }

    public function judgeVote($id)
    {
        $entry = ContestEntry::with('contest.scoringCategories')->findOrFail($id);

        priv_check('ContestJudge', $entry->contest)->ensureCan();

        $params = get_params(request()->all(), null, [
            'scores:array',
            'comment',
        ]);

        $categoryIds = new Set(array_pluck($params['scores'], 'contest_scoring_category_id'));
        $categoryIds->intersect(new Set($entry->contest->scoringCategories->pluck('id')));

        if ($categoryIds->count() !== $entry->contest->scoringCategories->count()) {
            throw new InvariantException(osu_trans('contest.judge.validation.missing_score'));
        }

        DB::transaction(function () use ($entry, $params) {
            $vote = $entry->judgeVotes()->firstOrNew(['user_id' => Auth::user()->getKey()]);
            $vote->fill(['comment' => $params['comment']])->save();

            foreach ($params['scores'] as $score) {
                $category = $entry->contest->scoringCategories
                    ->find($score['contest_scoring_category_id']);

                $value = clamp($score['value'], 0, $category->max_value);

                $vote->scores()->firstOrNew([
                    'contest_judge_vote_id' => $vote->getKey(),
                    'contest_scoring_category_id' => $category->getKey(),
                ])->fill(['value' => $value])->save();
            }
        });

        $updatedEntry = $entry->refresh()->load('judgeVotes.scores');

        return json_item($updatedEntry, 'ContestEntry', ['current_user_judge_vote.scores']);
    }

    public function vote($id)
    {
        $user = Auth::user();
        $entry = ContestEntry::findOrFail($id);
        $contest = Contest::with('entries')->with('entries.contest')->findOrFail($entry->contest_id);

        if ($contest->isJudged()) {
            throw new InvariantException(osu_trans('contest.judge.validation.contest_vote_judged'));
        }

        priv_check('ContestVote', $contest)->ensureCan();

        $contest->vote($user, $entry);

        return $contest->defaultJson($user);
    }

    public function store()
    {
        if (Request::hasFile('entry') !== true) {
            abort(422, 'No file uploaded');
        }

        $user = Auth::user();
        $contest = Contest::findOrFail(Request::input('contest_id'));
        $file = Request::file('entry');

        priv_check('ContestEntryStore', $contest)->ensureCan();

        $allowedExtensions = [];
        $maxFilesize = 0;
        switch ($contest->type) {
            case 'art':
                $allowedExtensions[] = 'jpg';
                $allowedExtensions[] = 'jpeg';
                $allowedExtensions[] = 'png';
                $maxFilesize = 8 * 1024 * 1024;
                break;
            case 'beatmap':
                $allowedExtensions[] = 'osu';
                $allowedExtensions[] = 'osz';
                $maxFilesize = 32 * 1024 * 1024;
                break;
            case 'music':
                $allowedExtensions[] = 'mp3';
                $maxFilesize = 16 * 1024 * 1024;
                break;
        }

        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions, true)) {
            abort(
                422,
                'Files for this contest must have one of the following extensions: '.implode(', ', $allowedExtensions)
            );
        }

        if ($file->getSize() > $maxFilesize) {
            abort(413, 'File exceeds max size');
        }

        if ($contest->type === 'art' && !is_null($contest->getForcedWidth()) && !is_null($contest->getForcedHeight())) {
            if (empty($file->getContent())) {
                abort(422, 'File must not be empty');
            }

            [$width, $height] = read_image_properties_from_string($file->getContent()) ?? [null, null];

            if ($contest->getForcedWidth() !== $width || $contest->getForcedHeight() !== $height) {
                abort(
                    422,
                    "Images for this contest must be {$contest->getForcedWidth()}x{$contest->getForcedHeight()}"
                );
            }
        }

        UserContestEntry::upload($file, $user, $contest);

        return $contest->userEntries($user);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $entry = UserContestEntry::where(['user_id' => $user->user_id])->findOrFail($id);
        $contest = Contest::findOrFail($entry->contest_id);

        priv_check('ContestEntryDestroy', $entry)->ensureCan();

        $entry->deleteWithFile();

        return $contest->userEntries($user);
    }
}
