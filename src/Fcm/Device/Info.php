<?php

namespace Fcm\Device;

use Exception;
use GuzzleHttp\Client;

class Info{

    private string $deviceId;
    private bool $details = false;
    private array $headers;

    public function __construct(array $serviceJson, object $guzzleClient = null)
    {
        if ($guzzleClient){
            $this->headers = $guzzleClient->getConfig()['headers']+["Authorization" => "key=".$serviceJson['key']];
        } else {
            $this->headers = ["Authorization" => "key=".$serviceJson['key']];
        }
    }

    public function setDeviceId(string $deviceId):object
    {
        if(empty($deviceId)) {
            throw new Exception('Device id is empty');
        }

        if (!is_string($deviceId)) {
            throw new Exception('Device id must be string');
        }

        $this->deviceId = $deviceId;

        return $this;
    }

    public function setDetailValue(bool $details):object
    {
        if (empty($details)) {
            throw new Exception('Details is empty');
        }

        if (!is_bool($details)) {
            throw new Exception('Details must be boolean');
        }

        $this->details = $details;

        return $this;
    }

    public function getUrl(){
        $url = "https://iid.googleapis.com/iid/info/{$this->deviceId}";
        if ($this->details) {
            $url .= "?details=$this->details";
        }
        return $url;
    }

    public function getBody(){
        return [];
    }

    public function getGuzzleClient():object
    {
        return new Client([
            'headers' => $this->headers
        ]);
    }

    public function send()
    {
        $client = $this->getGuzzleClient();
        $url = $this->getUrl();
        $response = $client->post($url, [
            'json' => $this->getBody()
        ]);
        $body = json_decode($response->getBody()->getContents(), true);
        if ($body === null || json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to json decode response body: '.json_last_error_msg());
        }
        if (isset($body['error'])){
            return $this->response($body['error']['code'], $body['error']['message']);
        }
        return $this->response(200, 'Device info',$body);
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
