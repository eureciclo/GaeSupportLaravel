<?php

namespace A1comms\GaeSupportLaravel\Integration\TaskQueue;

use Google\Cloud\Tasks\V2beta3\Task;
use Google\Cloud\Tasks\V2beta3\AppEngineRouting;
use Google\Cloud\Tasks\V2beta3\AppEngineHttpRequest;

class PushTask
{
    private $task;
    private $pushTask;

    public function __construct($url_path, $query_data = [], $options = []) {
        $this->pushTask = new AppEngineHttpRequest();

        $this->pushTask->setRelativeUri($url_path);
        $this->pushTask->setBody(http_build_query($query_data));
        $this->pushTask->setHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);

        if (gae_service() != "default") {
            $routing = new AppEngineRouting();
            $routing->setService(gae_service());

            $this->pushTask->setAppEngineRouting($routing);
        }

        if (in_array('method', $options)) {
            $this->pushTask->setMethod($options['method']);
        }

        $this->task = new Task();
        $this->task->setAppEngineHttpRequest($this->pushTask);

        if (in_array('delay_seconds', $options)) {
            $secondsString = sprintf('+%s seconds', $options['delay_seconds']);
            $futureTime = date(\DateTime::RFC3339, strtotime($secondsString));
            $this->task->setScheduleTime($futureTime);
        }
    }

    public function getTask() {
        return $this->task;
    }

    public function add($queue_name = 'default') {
        $queue = new PushQueue($queue_name);
        return $queue->addTasks([$this])[0];
    }
}