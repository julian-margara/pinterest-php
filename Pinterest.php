<?php

/**
 * Description of Pinterest
 *
 * @author Julian Margara <julian@socialtools.me>
 */
class Pinterest {

    const API_BASE = 'https://api.pinterest.com/v1/';
    const OAUTH_BASE = 'https://api.pinterest.com/oauth/';

    private $client_id = '';
    private $client_secret = '';
    private $access_token;

    function __construct($access_token = false) {
        if ($access_token) {
            $this->access_token = $access_token;
        }
    }

    function getAccess_token() {
        return $this->access_token;
    }

    function setAccess_token($access_token) {
        $this->access_token = $access_token;
    }

    public function getLoginUrl(array $scope = array(), $redirect_uri = false, $state = false) {
        if ($scope) {
            $scope = implode(',', $scope);
        }
        if (!$state) {
            $state = uniqid('', true);
        }

        $params = array(
            'response_type' => 'token',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'state' => $state,
            'scope' => $scope,
            'redirect_uri' => $redirect_uri,
        );

        $url = self::OAUTH_BASE . '?' . http_build_query($params);

        return $url;
    }

    public function getUser($user_id = false, $fields = array()) {
        if (!$fields) {
            $fields = array('id', 'username', 'first_name', 'last_name', 'image');
        }
        if ($user_id) {
            return $this->get('users/' . $user_id, $fields);
        } else {
            return $this->get('me', $fields);
        }
    }

    public function getBoards($fields = array()) {
        if (!$fields) {
            $fields = array('id', 'name');
        }
        return $this->get('me/boards', $fields);
    }

    public function getBoard($board_id, $fields = array()) {
        if (!$fields) {
            $fields = array('id', 'name');
        }
        return $this->get('boards/' . $board_id, $fields);
    }

    public function getBoardPins($board_id, $fields = array()) {
        if (!$fields) {
            $fields = array('id', 'note', 'image(original)');
        }
        return $this->get('boards/' . $board_id . '/pins', $fields);
    }

    public function getPins($fields = array()) {
        if (!$fields) {
            $fields = array('id', 'note', 'image(original)', 'board(id,name)');
        }
        return $this->get('me/pins', $fields);
    }

    public function getPin($pin_id, $fields = array()) {
        if (!$fields) {
            $fields = array('id', 'note', 'image(original)');
        }
        return $this->get('pins/' . $pin_id, array('id', 'note', 'image(original)'));
    }

    public function createPin($board_id, $message, $url, $image) {
        $params = array(
            'board' => $board_id,
            'note' => $message,
            'link' => $url,
        );

        if (is_string($image)) {
            $params['image_url'] = $image;
        } else {
            $params['image'] = $image;
        }

        return $this->post('pins', $params);
    }

    public function createBoard($name, $description) {
        $params = array(
            'name' => $name,
            'description' => $description,
        );
        return $this->post('boards', $params);
    }

    public function deletePin($pin_id) {
        return $this->delete('pins/' . $pin_id);
    }

    public function deleteBoard($board_id) {
        return $this->delete('boards/' . $board_id);
    }

    private function post($endpoint, $params = array()) {
        return $this->request($endpoint, $params, 'POST');
    }

    private function get($endpoint, $fields = '', $params = array()) {
        if (is_array($fields)) {
            $params['fields'] = implode(',', $fields);
        }
        return $this->request($endpoint, $params, 'GET');
    }

    private function delete($endpoint, $params = array()) {
        return $this->request($endpoint, $params, 'DELETE');
    }

    private function request($endpoint, $params, $method) {
        $ch = curl_init();

        $request_url = self::API_BASE . $endpoint;

        if ($this->access_token) {
            $request_url .= '?access_token=' . $this->access_token;
        }

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
            case 'GET':
            case 'DELETE':
                $request_url .= '&' . http_build_query($params);
                break;
        }

        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if(array_key_exists('data', $data)) {
            return $data['data'];
        } else {
            return array('error' => $data);
        }
    }

}
