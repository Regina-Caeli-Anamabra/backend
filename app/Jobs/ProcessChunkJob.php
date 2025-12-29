<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProcessChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $records;
    /**
     * Create a new job instance.
     */
    public function __construct($records)
    {
        $this->records = $records;
    }

    /**
     * Execute the job.
     */
    public function handle($records): void
    {

            foreach ($records as $record) {
                DB::transaction(function () use ($record) {
                    // Insert data into the target table
                    $insertedId = DB::table('users')->insertGetId([
                        'phone' => $record->phone_no,
                        'password' => Hash::make("12345"),
                        'verified' => 1,
                    ]);

                    // Use data from the inserted row to update another table (mytable)
                    DB::table('patient')
                        ->where('id', $record->id) // Update based on a related field or condition
                        ->update([
                            'user_id' => $insertedId, // Optionally store the new ID in mytable
                            'updated_at' => now(),
                        ]);
                });
            }

    }
}
