<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Event;

class EventController extends Controller
{
    public function __construct(){
      $this->middleware(
        'jwt.auth',
        ['except' => ['index','show']]
      );
    }

    public function index()
    {
        $events = Event::all();
        foreach ($events as $event) {
          $event->view_event = [
            'href' => 'api/v1/event/'.$event->id,
            'method' => 'GET'
          ];
        }

        $response = [
          'msg' => 'List of all Events',
          'events' => $events
        ];

        return response()->json($response,200);
    }


    public function store(Request $request)
    {
      //validasi
      $this->validate($request,[
        'title' => 'required',
        'description' => 'required',
        'time' => 'required',
        'user_id' => 'required'
      ]);
      //membuat variabel setiap inputan
        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $request->input('user_id');

        $event = new Event([
          'title' => $title,
          'description' => $description,
          'time' => $time
        ]);

        if($event->save()){
          $event->users()->attach($user_id);
          $event->view_event=[
            'href' => 'api/v1/event/'.$event->id,
            'method' => 'GET'
          ];

          $message = [
            'msg' => 'Event created',
            'event' => $event
          ];
          return response()->json($message,201);
        }
      $response = [
        'msg' => 'Error during creation',
      ];

      return response()->json($response,404);
    }

    public function show($id)
    {
        $event = Event::with('users')->where('id',$id)->firstOrFail();
        $event->view_events = [
          'href' => 'api/v1/event',
          'method' => 'GET'
        ];

        $response = [
          'msg' => 'Event information',
          'event' => $event
        ];

        return response()->json($response,200);

    }

    public function update(Request $request, $id)
    {
      //validasi
      $this->validate($request,[
        'title' => 'required',
        'description' => 'required',
        'time' => 'required|date_format:Y-m-d H:i:s',
        'user_id' => 'required'
      ]);

      $title = $request->input('title');
      $description = $request->input('description');
      $time = $request->input('time');
      $user_id = $request->input('user_id');

      $event = Event::with('users')->findOrFail($id);

      if(!$event->users()->where('users.id',$user_id)->first()){
        return response()->json(['msg' => 'user not registered for event, update not successful'],401);
      }

      $event->time = $time;
      $event->title = $title;
      $event->description = $description;

      if(!$event->update()){
        return response()->json([
          'msg' => 'Error during updating',
        ],404);
      }

      $event->view_event=[
        'href' => 'api/v1/event/'.$event->id,
        'method' => 'GET'
      ];

      $response = [
        'msg' => 'Event Updated',
        'event' => $event
      ];

      return response()->json($response,200);
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $users = $event->users;
        $event->users()->detach(); //melepaskan relasi user dan event

        if(!$event->delete()){
          foreach ($users as $user) {
            $event->users()->attach($user);
          }
          return response()->json([
            'msg' => 'Deletion Failed',
          ],404);
        }

        $response = [
          'msg' => 'Event deleted',
          'create' => [
            'href' => 'api/v1/event',
            'method' => 'POST',
            'params' => 'title,description,time'
          ]
        ];
        return response()->json($response,200);
    }
}
