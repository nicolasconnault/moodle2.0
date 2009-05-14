<?php

class wikimedia {
    private $_conn  = null;
    private $_param = array();
    public function __construct($url = '') {
        if (empty($url)) {
            $this->api = 'http://commons.wikimedia.org/w/api.php';
        }
        $this->_param['format'] = 'php';
        $this->_param['redirects'] = true;
        $this->_conn = new curl(array('cache'=>true, 'debug'=>false));
    }
    public function login($user, $pass) {
        $this->_param['action']   = 'login';
        $this->_param['lgname']   = $user;
        $this->_param['lgpassword'] = $pass;
        $content = $this->_conn->post($this->api, $this->_param);
        $result = unserialize($content);
        if (!empty($result['result']['sessionid'])) {
            $this->userid = $result['result']['lguserid'];
            $this->username = $result['result']['lgusername'];
            $this->token = $result['result']['lgtoken'];
            return true;
        } else {
            return false;
        }
    }
    public function logout() {
        $this->_param['action']   = 'logout';
        $content = $this->_conn->post($this->api, $this->_param);
        return;
    }
    public function get_image_url($titles) {
        $image_urls = array();
        $this->_param['action'] = 'query';
        if (is_array($titles)) {
            foreach ($titles as $title) {
                $this->_param['titles'] .= ('|'.urldecode($title));
            }
        } else {
            $this->_param['titles'] = urldecode($title);
        }
        $this->_param['prop']   = 'imageinfo';
        $this->_param['iiprop'] = 'url';
        $content = $this->_conn->post($this->api, $this->_param);
        $result = unserialize($content);
        foreach ($result['query']['pages'] as $page) {
            if (!empty($page['imageinfo'][0]['url'])) {
                $image_urls[] = $page['imageinfo'][0]['url'];
            }
        }
        return $image_urls;
    }
    public function get_images_by_page($title) {
        $image_urls = array();
        $this->_param['action'] = 'query';
        $this->_param['generator'] = 'images';
        $this->_param['titles'] = urldecode($title);
        $this->_param['prop']   = 'images|info|imageinfo';
        $this->_param['iiprop'] = 'url';
        $content = $this->_conn->post($this->api, $this->_param);
        $result = unserialize($content);
        if (!empty($result['query']['pages'])) {
            foreach ($result['query']['pages'] as $page) {
                $image_urls[$page['title']] = $page['imageinfo'][0]['url'];
            }
        }
        return $image_urls;
    }
    public function search_images($title) {
        $image_urls = array();
        $this->_param['action'] = 'query';
        $this->_param['generator'] = 'search';
        $this->_param['gsrsearch'] = $title;
        $this->_param['gsrnamespace'] = 6;
        $this->_param['prop']   = 'imageinfo';
        $this->_param['iiprop'] = 'url';
        $content = $this->_conn->post($this->api, $this->_param);
        $result = unserialize($content);
        if (!empty($result['query']['pages'])) {
            foreach ($result['query']['pages'] as $page) {
                $image_urls[$page['title']] = $page['imageinfo'][0]['url'];
            }
        }
        return $image_urls;
    }
}
