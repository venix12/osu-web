// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

import GenreJson from 'interfaces/genre-json';
import LanguageJson from 'interfaces/language-json';
import BeatmapJson from '../interfaces/beatmap-json';

interface BeatmapsetCovers {
  card: string;
  cover: string;
  list: string;
  slimcover: string;
}

// TODO: incomplete
export interface BeatmapsetJson {
  artist: string;
  beatmaps?: BeatmapJson[];
  covers: BeatmapsetCovers;
  creator: string;
  genre: GenreJson;
  id: number;
  language: LanguageJson;
  title: string;
  user_id: number;
}
