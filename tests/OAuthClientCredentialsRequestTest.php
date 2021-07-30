<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace Tests;

use App\Models\OAuth\Client;
use App\Models\User;

class OAuthClientCredentialsRequestTest extends TestCase
{
    /**
     * @dataProvider requestingScopeDataProvider
     */
    public function testBotRequestingScope($scope, $status)
    {
        $owner = factory(User::class)->states('bot')->create();
        $client = factory(Client::class)->create([
            'redirect' => 'https://localhost',
            'user_id' => $owner->getKey(),
        ]);

        $params = [
            'client_id' => $client->getKey(),
            'client_secret' => $client->secret,
            'grant_type' => 'client_credentials',
            'scope' => $scope,
        ];

        $this->post(route('oauth.passport.token'), $params)
            ->assertStatus($status);
    }

    /**
     * @dataProvider requestingScopeDataProvider
     */
    public function testNonBotRequestingScope($scope, $status)
    {
        $owner = factory(User::class)->create();
        $client = factory(Client::class)->create([
            'redirect' => 'https://localhost',
            'user_id' => $owner->getKey(),
        ]);

        $params = [
            'client_id' => $client->getKey(),
            'client_secret' => $client->secret,
            'grant_type' => 'client_credentials',
            'scope' => $scope,
        ];

        $this->post(route('oauth.passport.token'), $params)
            ->assertStatus($status);
    }

    public function requestingScopeDataProvider()
    {
        return [
            '* cannot be requested' => ['*', 400],
            'cannot request empty scope' => ['', 400],
            'bot scope allows chat.write' => ['bot chat.write', 200],
            'chat.write cannot be requested by itself' => ['chat.write', 400],
            'mixing scope delegation is not allowed' => ['bot chat.write forum.write', 400],
            'public scope is allowed' => ['public', 200],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // otherwise exceptions won't render the actual view.
        config()->set('app.debug', false);
    }
}
