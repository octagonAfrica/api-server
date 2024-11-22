<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EventsController extends Controller
{
    public function allEvents(Request $request)
    {
        $sql = 'SELECT e.*, COUNT(el.id) AS followers
            FROM events e
            LEFT JOIN event_likes el ON e.event_id = el.event_id
            GROUP BY e.id, e.start_date, e.end_date, e.start_time, e.end_time, e.event_organizer, e.event_image, e.event_title, e.event_id';

        $events = DB::connection('mydb_sqlsrv')->select($sql);

        // Iterate through the events and apply custom transformations
        foreach ($events as $event) {
            $event->start_date = date('Y-m-d', strtotime($event->start_date));
            $event->end_date = date('Y-m-d', strtotime($event->end_date));
            $event->start_time = date('H:i:s', strtotime($event->start_time));
            $event->end_time = date('H:i:s', strtotime($event->end_time));
            $event->event_organizer = trim($event->event_organizer);
            $event->event_image = trim($event->event_image);
            $event->event_title = trim($event->event_title);
            $event->event_id = trim($event->event_id);
            $event->followers = intval($event->followers);
        }

        return response()->json([
            'status' => 200,
            'operation' => 'success',
            'message' => 'All Events',
            'data' => $events,
        ], 200);
    }

    public function likeEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'event_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all(),
            ], 400);
        }
        $user_id = $request->input('user_id');
        $event_id = $request->input('event_id');

        $likes = DB::connection('mydb_sqlsrv')->insert('INSERT INTO event_likes (event_id, user_id) VALUES (?, ?)', [$event_id, $user_id]);

        return response()->json([
            'status' => 200,
            'operation' => 'success',
            'message' => 'Event Liked',
        ], 200);
    }
}
