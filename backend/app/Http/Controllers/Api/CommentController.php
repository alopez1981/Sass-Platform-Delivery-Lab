<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Request as OperationalRequest;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, OperationalRequest $operationalRequest)
    {
        $this->authorize('comment', $operationalRequest);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment = $operationalRequest->comments()->create([
            'organization_id' => $operationalRequest->organization_id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return response()->json($comment->load('user:id,name'), 201);
    }
}
