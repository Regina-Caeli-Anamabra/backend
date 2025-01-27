<?php

namespace App\Jobs;

use App\Notifications\JobCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class MovePatientsToUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $chunkSize = 2; // Adjust based on memory and performance requirements
        $limit = 10;     // Total rows to process
        $processed = 0;  // Counter for processed rows

        try {
            \App\Models\Patients::where('moved', 0)
                ->limit(20)
                ->chunk($chunkSize, function ($patients)  use ($processed, $limit) {
                    foreach ($patients as $patient) {
                        if ($processed >= $limit) {
                            // Stop processing if we've reached the limit
                            return false;
                        }
                        Log::info("moved patient {$patient->id}");
                        DB::transaction(function () use ($patient) {
                            // Insert data into the target table
                            $user = new \App\Models\User();
                            $user->phone = $patient->phone_no;
                            $user->reg_id = $patient->patient_id;
                            $user->verified = 1;
                            $user->password = Hash::make('12345');
                            $user->save();

                            // Update the 'patients' table
                            DB::table('patient') // Ensure the table name matches your schema
                            ->where('id', $patient->id)
                                ->update([
                                    'user_id' => $user->id, // Use the newly created user's ID
                                    'moved' => 1,
                                    'updated_at' => now(),
                                ]);
                        });
                    }
                });

            // Send a notification once the job is completed
            $recipientEmail = 'chuksdsilent@gmail.com'; // Specify the recipient email directly

            // Send the notification to the recipient email
            Notification::route('mail', $recipientEmail)
                ->notify(new JobCompletedNotification($recipientEmail));
        } catch (\Exception $e) {
            // Log the error and handle any recovery
            Log::error('Job failed: ' . $e->getMessage());
        }
    }
}
