<?php

namespace App\Notifications;

use App\Models\WeekMonitor;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class WorkloadNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $weekMonitor;
    protected $weekMonitorId;

    /**
     * Create a new notification instance.
     */
    public function __construct(WeekMonitor $weekMonitor)
    {
        //
        $this->weekMonitor = $weekMonitor;
        $this->weekMonitorId = $weekMonitor->id;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // return ['mail', 'database'];
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Workload has exceeded 80 hours in a week",
            'pesan' => "Jam kerja telah melebihi 80 jam dalam satu minggu",
            'userId' => $notifiable->id,
            'userFullname' => $notifiable->fullname,
            'userIdentity' => $notifiable->identity,
            'weekMonitorId' => $this->weekMonitor->id,
            'weekGroupId' => $this->weekMonitor->week_group_id,
            'week' => $this->weekMonitor->week,
            'year' => $this->weekMonitor->year,
            'workload' => $this->weekMonitor->workload,
        ];
    }
}
