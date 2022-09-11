<?php

namespace Sabbir\FcmCloudMessaging\Fcm\Push;

use Exception;

class Notification{
    private string $recipients = "";
    private array $topics = [];
    private array $data = [];
    private array $notification=[];
    private object $client;
    private array $serviceJson;

    public function __construct(array $serviceJson, object $guzzleClient = null)
    {
        $this->serviceJson = $serviceJson;
        $this->authorize($guzzleClient);
    }

    private function authorize(object $guzzleClient = null):void
    {
        $this->client = new \Google\Client();
        $this->client->setAuthConfig($this->serviceJson);
        if($guzzleClient) {
            $this->client->setHttpClient($guzzleClient);
        }
        $this->client->addScope(\Google\Service\FirebaseCloudMessaging::CLOUD_PLATFORM);
        $this->client = $this->client->authorize();
    }

    public function setData(array $data):object
    {
        $this->data = $data;
        return $this;
    }

    public function setNotification(array $notification):object
    {
        $this->notification = $notification;
        return $this;
    }

    public function getBody():array
    {
        if(!$this->recipients && empty($this->topics)) {
            throw new Exception('Must minimally specify a single recipient or topic.');
        }
        if (!$this->recipients && !empty($this->topics)) {
            throw new Exception('You cannot use both recipient and topic.You can use only one.');
        }
        $data["message"] = [];
        // serialize fcm token as recipients
        if ($this->recipients) {
            $data["message"]["token"] = $this->recipients;
        }
        // serialize topics
        if (!empty($this->topics)) {
            $data["message"]["condition"] = array_reduce($this->topics, function ($carry, string $topic) {
                    $topicSyntax = "'%s' in topics";
                    if (end($this->topics) === $topic) {
                        return $carry .= sprintf($topicSyntax, $topic);
                    }
                    return $carry .= sprintf($topicSyntax, $topic) . '||';
                });
        }
        // serialize data
        if (!empty($this->data)) {
            $data["message"]['data'] = $this->data;
        }
        // serialize notification
        if (!empty($this->notification)) {
            $data["message"]["notification"] =  $this->notification;
        }

        return $data;
    }

    public function addRecipient(string $fcm_token):object
    {
        $this->recipients = $fcm_token;
        return $this;
    }

    public function addTopic($topic):object
    {
        if (\is_string($topic)) {
            $this->topics[] = $topic;
        }
        if (\is_array($topic)) {
            $this->topics = array_merge($this->topics, $topic);
        }
        return $this;
    }

    public function addData($name, $value):object
    {
        $this->data[$name] = $value;
        return $this;
    }

    public function send():array
    {
        $response = $this->client->post("https://fcm.googleapis.com/v1/projects/".trim($this->serviceJson['project_id'])."/messages:send", [
            'body' => json_encode($this->getBody())
        ]);
        $body = json_decode($response->getBody()->getContents(), true);
        if ($body === null || json_last_error() !== JSON_ERROR_NONE) {
            return $this->response(500, 'Failed to json decode response body: '.json_last_error_msg());
        }
        if (isset($body['error'])){
            return $this->response($body['error']['code'], $body['error']['message']);
        }
        return $this->response(200, $body['name']);
    }

    private function response(int $code, string $message, array $data = []):array
    {
        return [
            'code' => $code,
            'message' =>   $message,
            'data' => $data
        ];
    }
}
