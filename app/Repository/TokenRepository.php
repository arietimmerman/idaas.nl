<?php

/**
 * Extends Passport's TokenRepository.
 * Ensures tenant-scope is applied by making use of custom Token model. And auto cleans up tokens ...
 */

namespace App\Repository;

use App\Token;
use Carbon\Carbon;
use Laravel\Passport\TokenRepository as LaravelTokenRepository;

class TokenRepository extends LaravelTokenRepository
{
    /**
     * Creates a new Access Token.
     *
     * @param  array  $attributes
     * @return \Laravel\Passport\Token
     */
    public function create($attributes)
    {
        return Token::create($attributes);
    }

    /**
     * Get a token by the given ID.
     *
     * @param  string  $id
     * @return \Laravel\Passport\Token
     */
    public function find($id)
    {
        return Token::find($id);
    }

    /**
     * Get a token by the given user ID and token ID.
     *
     * @param  string  $id
     * @param  int  $userId
     * @return \Laravel\Passport\Token|null
     */
    public function findForUser($id, $userId)
    {
        return Token::where('id', $id)->where('user_id', $userId)->first();
    }

    /**
     * Get the token instances for the given user ID.
     *
     * @param  mixed  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser($userId)
    {
        return Token::where('user_id', $userId)->get();
    }

    /**
     * Get a valid token instance for the given user and client.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  \Laravel\Passport\Client  $client
     * @return \Laravel\Passport\Token|null
     */
    public function getValidToken($user, $client)
    {
        return $client->tokens()
            ->whereUserId($user->getKey())
            ->whereRevoked(0)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Store the given token instance.
     *
     * @return void
     */
    public function save(\Laravel\Passport\Token $token)
    {
        $token->save();
    }

    /**
     * Revoke an access token.
     *
     * @param  string  $id
     * @return mixed
     */
    public function revokeAccessToken($id)
    {
        return Token::where('id', $id)->update(['revoked' => true]);
    }

    /**
     * Check if the access token has been revoked.
     *
     * @param  string  $id
     * @return bool Return true if this token has been revoked
     */
    public function isAccessTokenRevoked($id)
    {
        if ($token = $this->find($id)) {
            return $token->revoked;
        }

        return true;
    }

    /**
     * Find a valid token for the given user and client.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  \Laravel\Passport\Client  $client
     * @return \Laravel\Passport\Token|null
     */
    public function findValidToken($user, $client)
    {
        return $client->tokens()
            ->whereUserId($user->getKey())
            ->whereRevoked(0)
            ->where('expires_at', '>', Carbon::now())
            ->latest('expires_at')
            ->first();
    }
}
