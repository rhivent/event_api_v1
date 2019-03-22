<?php

namespace App\Http\Controllers;

use App\User;
use App\Event;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function __construct(){
      $this->middleware('jwt.auth');
    }

    public function store(Request $request)
    {
        $this->validate($request,[
          'event_id' => 'required',
          'user_id' => 'required',
        ]);

        $event_id = $request->input('event_id');
        $user_id = $request->input('user_id');

        $event = Event::findOrFail($event_id);
        $user = User::findOrFail($user_id);

        $message = [
            'msg' => 'User is already registered for event',
            'user' => $user,
            'event' => $event,
            'unregistered' => [
              'href' => 'api/v1/event/registration/'.$event->id,
              'method' => 'DELETE',
            ]
        ];

        if($event->users()->where('users.id',$user->id)->first()){
          return response()->json($message,404);
        }

        $user->events()->attach($event); // membaut relasi user dan event
        $response = [
          'msg' => 'User registered for event',
          'event' => $event,
          'user' => $user,
          'unregistered' => [
            'href' => 'api/v1/event/registration/'.$event->id,
            'method' => 'DELETE']
        ];
        return response()->json($response,201);
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->users()->detach();

        $response = [
          'msg' => 'User unregistered for event',
          'event' => $event,
          'user' => 'tbd',
          'register' => [
            'href' => 'api/v1/event/registration/',
            'method' => 'POST',
            'params' => 'user_id,event_id'
          ]
        ];

        return response()->json($response,200);
    }
}
