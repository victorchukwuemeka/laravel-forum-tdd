<?php

namespace App\Http\Controllers;

use App\Channel;
use App\Inspections\Spam;
use App\Reply;
use App\Thread;


class RepliesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index']);
    }

    public function index(Channel $channel, Thread $thread)
    {
        return $thread->replies()->paginate(10);
    }
    
    public function store($channel_id, Thread $thread)
    {
        try {
            $this->validateReply();

            $reply = $thread->addReply([
                'body' => request('body'),
                'user_id' => auth()->id()
            ]);
        }catch(\Exception $e) {
            return response('Sorry, your reply can be proccess at this time.', 422);
        }

        if (request()->expectsJson()) {
            return response($reply->load('owner'), 200);
        }

        return back()->with('flash', 'A reply was added');
    }

    public function update(Reply $reply)
    {
        $this->authorize('update', $reply);

        try {
            $this->validateReply();
            $reply->update(request(['body']));
        }catch(\Exception $e) {
            return response('Sorry, your reply can be proccess at this time.', 422);
        }
    }

    public function destroy (Reply $reply)
    {
        $this->authorize('update', $reply);

        $reply->delete();

        if (request()->wantsJson()) {
            return response(['status' => 'Your reply has been deleted']);
        }

        return back()->with('flash', 'Reply was deleted with success');
    }

    protected function validateReply()
    {
        $this->validate(request(), [
            'body' => 'required'
        ]);

        resolve(Spam::class)->detect(request('body'));
    }
}
