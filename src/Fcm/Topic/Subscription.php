<?php

namespace Fcm\Topic;

use Exception;
use GuzzleHttp\Client;

class Subscription{
    private array $devices = [];
    private string $topicName;
    private bool $subscriptionType = true;
    private array $headers;

    public function __construct(array $serviceJson, object $guzzleClient = null)
    {
        if ($guzzleClient){
            $this->headers = $guzzleClient->getConfig()['headers']+["Authorization" => "key=".$serviceJson['key']];
        } else {
            $this->headers = ["Authorization" => "key=".$serviceJson['key']];
        }
    }

    public function setTopic(string $topic){
        $this->topicName = $topic;
        return $this;
    }

    public function setSubscriptionType(bool $subscriptionType){
        $this->subscriptionType = $subscriptionType;
        return $this;
    }

    public function addDevice($deviceId){
        if (empty($deviceId)) {
            throw new Exception('Device id is empty');
        }
        if (\is_string($deviceId)) {
            $this->devices[] = $deviceId;
        }
        if (\is_array($deviceId)) {
            $this->devices = array_merge($this->devices, $deviceId);
        }

        return $this;
    }

    public function getBody(){
        return [
            'to' => "/topics/{$this->topicName}",
            'registration_tokens' => $this->devices,
        ];
    }

    public function getGuzzleClient():object
    {
        return new Client([
            'headers' => $this->headers
        ]);
    }

    public function send(){
        $url = '';
        if($this->subscriptionType){
            $url = 'https://iid.googleapis.com/iid/v1:batchAdd';
        }else {
            $url = 'https://iid.googleapis.com/iid/v1:batchRemove';
        }
        $client = $this->getGuzzleClient();
        $response = $client->post($url, [
            'json' => $this->getBody()
        ]);
        $body = json_decode($response->getBody()->getContents(), true);
        if ($body === null || json_last_error() !== JSON_ERROR_NONE) {
            return $this->response(500, 'Failed to json decode response body: '.json_last_error_msg());
        }
        if (isset($body['error'])){
            return $this->response($body['error']['code'], $body['error']['message']);
        }
        return $this->response(200, 'topic added successfully');
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
