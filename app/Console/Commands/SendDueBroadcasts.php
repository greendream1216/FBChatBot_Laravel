<?php namespace App\Console\Commands;

use App\Models\Broadcast;
use App\Jobs\SendBroadcast;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Services\BroadcastService;
use App\Repositories\Broadcast\BroadcastRepositoryInterface;
use App\Repositories\Subscriber\SubscriberRepositoryInterface;

class SendDueBroadcasts extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and run active broadcasts.';
    /**
     * @type BroadcastRepositoryInterface
     */
    private $broadcastRepo;
    /**
     * @type BroadcastService
     */
    private $broadcasts;
    /**
     * @type SubscriberRepositoryInterface
     */
    private $subscriberRepo;


    /**
     * Broadcast constructor.
     * @param BroadcastRepositoryInterface  $broadcastRepo
     * @param BroadcastService              $broadcasts
     * @param SubscriberRepositoryInterface $subscriberRepo
     */
    public function __construct(
        BroadcastService $broadcasts,
        BroadcastRepositoryInterface $broadcastRepo,
        SubscriberRepositoryInterface $subscriberRepo
    ) {
        parent::__construct();
        $this->broadcasts = $broadcasts;
        $this->broadcastRepo = $broadcastRepo;
        $this->subscriberRepo = $subscriberRepo;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $broadcasts = $this->broadcastRepo->getDueBroadcasts();

        /** @var Broadcast $broadcast */
        foreach ($broadcasts as $broadcast) {
            $this->processBroadcast($broadcast);
        }
    }

    /**
     * @param Broadcast $broadcast
     */
    private function processBroadcast(Broadcast $broadcast)
    {
        $this->markAsRunning($broadcast);

        $this->run($broadcast);

        $this->scheduleNextRunAndMarkAsCompleted($broadcast);
    }

    /**
     * @param Broadcast $broadcast
     */
    protected function markAsRunning(Broadcast $broadcast)
    {
        $this->broadcastRepo->update($broadcast, ['status' => 'running']);
    }

    public function run(Broadcast $broadcast)
    {
        $subscribers = $this->getTargetAudience($broadcast);

        foreach ($subscribers as $subscriber) {
            dispatch(new SendBroadcast($broadcast, $subscriber));
        }
    }

    /**
     * @param Broadcast $broadcast
     */
    private function scheduleNextRunAndMarkAsCompleted(Broadcast $broadcast)
    {
        $data = $this->broadcasts->calculateNextScheduleDateTime($broadcast);

        if (is_null($data['next_send_at'])) {
            $data['status'] = 'completed';
        }

        $this->broadcastRepo->update($broadcast, $data);
    }

    /**
     * @param $broadcast
     * @return Collection
     */
    protected function getTargetAudience(Broadcast $broadcast)
    {
        $filters = [];
        if ($broadcast->timezone != 'same_time') {
            $filters[] = [
                'operator'  => '=',
                'attribute' => 'timezone',
                'value'     => $broadcast->next_utc_offset
            ];
        }

        $audience = $this->subscriberRepo->getActiveTargetAudience($broadcast, $filters);

        return $audience;
    }
}