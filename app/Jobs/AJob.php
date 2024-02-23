<?php

namespace App\Jobs;

use App\Constants\TaskStatus;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Storage;

class AJob implements ShouldQueue
{
    public $tries = 1;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $task;
    /**
     * @var callable
     */
    protected $cb_show;
    protected $type;

    /**
     * Create a new job instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
        $cb_show = function ($message) use ($task) {
            $task_id = $task->getKey();
            $type = $this->type;
            if (is_string($message)) {
                $message = "[$type][$task_id] Message: $message";
                error_log($message);
                Storage::disk('task')->append($task_id . '.log', $message);
            }
        };
        $task_id = $task->getKey();
        $cb_show('task-' . $task_id);
    }

    /**
     * Execute the job.
     */
    public function cb_handle($cb): void
    {
        $this->task->status = TaskStatus::PROCESSING;
        $this->task->start_at = Carbon::now();
        $jobID = $this->job->getJobId();
        $this->task->queue_name = $jobID;
        $this->task->save();

        $task = $this->task;
        $cb_show = function ($message) use ($task) {
            $task_id = $task->getKey();
            $type = $this->type;
            if (is_string($message)) {
                $message = "[$type][$task_id] Message: $message";
                error_log($message);
                Storage::disk('task')->append($task_id . '.log', $message);
            }
        };
        $cb_show('job-' . $jobID);
        $cb_show("---- time start: " . Carbon::now()->format('Y-m-d H:i:s') . "----");
        $this->cb_show = $cb_show;
        try {
            $cb_show("---- TASK START ----");
            $cb($cb_show);
            $this->task->status = TaskStatus::COMPLETED;
            $this->task->end_at = Carbon::now();
            $this->task->save();

            $cb_show("---- time end: " . Carbon::now()->format('Y-m-d H:i:s') . "----");
            $cb_show("---- TASK DONE ----");
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
    public function cb_failed(\Exception $exception, $cb = null): void
    {
        $this->task->status = TaskStatus::FAILED;
        $this->task->error = $exception->getMessage();
        $this->task->end_at = Carbon::now();
        $this->task->save();

        $task = $this->task;
        $cb_show = function ($message) use ($task) {
            $task_id = $task->getKey();
            $type = $this->type;
            if (is_string($message)) {
                $message = "[$type][$task_id] Message: $message";
                error_log($message);
                Storage::disk('task')->append($task_id . '.log', $message);
            }
        };
        if (!empty($cb)) {
            $cb($cb_show);
        }
        Storage::disk('task')->append($this->task->id . '.log', $exception);
        $cb_show("---- time end: " . Carbon::now()->format('Y-m-d H:i:s') . "----");
        $cb_show("---- TASK ERROR ----");
    }

}
