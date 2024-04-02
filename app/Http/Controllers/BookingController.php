<?php

namespace App\Http\Controllers;

use App\Models\Bookings;
use App\Models\r;
use App\Utils\Utils;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Utils $utils)
    {
        $request->validate([
            "user_id" => "required|int"
        ]);
        try {
            $booking = Bookings::where("user_id", $request->get("user_id"))->get();
            return $utils->message("success", $booking , 400);
        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Utils $utils)
    {
        $request->validate([
            "user_id" => "required|int",
            "booking_start" => "required",
            "service_id" => "required|int",
        ]);

        try {
            $booking_start = Carbon::parse($request->get("booking_start"));
            $booking_start_formatted =  $booking_start->format("Y-m-d H:i:s");
            $booking_end =  $booking_start->copy()->addMinute(45)->format("Y-m-d H:i:s");

            if(Bookings::whereBetween("session_start", [$booking_start_formatted, $booking_end])->exists())
                return $utils->message("error","The session is already booked." , 400);

            $booking = new Bookings();
            $booking->session_start = $booking_start_formatted;
            $booking->service_id = $request->get("service_id");
            $booking->session_end = $booking_end;
            $booking->user_id = $request->get("user_id");
            $booking->save();
            return $utils->message("success", $booking , 400);

        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(r $r)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(r $r)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, r $r)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(r $r)
    {
        //
    }
}
