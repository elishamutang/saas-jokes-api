<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Joke;
use App\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    /**
     * Allows a user to like a joke.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function like(Request $request, string $id): JsonResponse
    {
        // Get joke and user
        $joke = Joke::with('votes')->findOrFail((int) $id);
        $user = $request->user();

        // Check if user has voted on the joke.
        $vote = $joke->votes()->where('user_id', $user->id)->first();
        $voteRating = $vote ? $vote->rating : null;

        // If user has not voted OR user has previously disliked the joke, then
        // overwrite the vote's rating to 1.
        if (!$voteRating || $voteRating === -1) {
            // Overwrite rating
            $joke->votes()
                ->where('user_id', $user->id)
                ->updateOrCreate(
                    ['user_id' => $user->id],
                    ['rating' => 1]
                );

            $joke->refresh();
            return ApiResponse::success($joke, "Joke liked successfully");
        }

        return ApiResponse::error([], "You have already liked this joke.", 409);
    }

    /**
     * Allows a user to dislike a joke.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function dislike(Request $request, string $id): JsonResponse
    {
        // Get joke and user
        $joke = Joke::with('votes')->findOrFail((int) $id);
        $user = $request->user();

        // Check if user has voted on the joke.
        $vote = $joke->votes()->where('user_id', $user->id)->first();
        $voteRating = $vote ? $vote->rating : null;

        // If user has not voted OR user has previously liked the joke, then
        // overwrite the vote's rating to -1.
        if (!$voteRating || $voteRating === 1) {
            // Overwrite rating
            $joke->votes()
                ->where('user_id', $user->id)
                ->updateOrCreate(
                    ['user_id' => $user->id],
                    ['rating' => -1]
                );

            $joke->refresh();
            return ApiResponse::success($joke, "Joke disliked successfully");
        }

        return ApiResponse::error([], "You have already disliked this joke.", 409);
    }

    /**
     * Allows a user to remove their vote from a joke.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function removeVote(Request $request, string $id): JsonResponse
    {
        // Get joke and user
        $joke = Joke::with('votes')->findOrFail((int) $id);
        $user = $request->user();

        // Check if user has voted on the joke.
        $vote = $joke->votes()->where('user_id', $user->id)->first();
        $voteRating = $vote ? $vote->rating : null;

        // Remove vote from joke.
        if ($voteRating) {
            $joke->votes()
                ->where('user_id', $user->id)
                ->delete();

            $joke->refresh();
            return ApiResponse::success($joke, "Vote removed successfully");
        }

        return ApiResponse::error([], "Unable to remove non-existent vote. You have not voted on this joke.", 409);
    }
}
