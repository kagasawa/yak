<?php
// HTML_Emoji
App::import('Vendor', 'Yak.HTML_Emoji', array('file' => 'HTML' . DS . 'Emoji.php'));
App::uses('Component', 'Controller');
App::uses('CakeSession', 'Model/Datasource');

class YakComponent extends Component {
    private $emoji;

    /**
     * __construct
     *
     * @param ComponentCollection $collection instance for the ComponentCollection
     * @param array $settings Settings to set to the component
     * @return void
     */
    public function __construct(ComponentCollection $collection, $settings  =  array()) {
        $this->controller = $collection->getController();
        parent::__construct($collection, $settings);
    }

    /**
     * __call
     *
     * $methodName, $args
     * @returno
     */
    public function __call($methodName, $args){
        return call_user_func(array($this->emoji, $methodName), $args);
    }

    /**
     * initialize
     *
     * @param &$controller
     * @return
     */
    public function initialize($controller) {
        $this->params = $this->controller->request->params;

        $this->emoji = HTML_Emoji::getInstance();
        $this->emoji->setImageUrl(Router::url('/') . 'yak/img/');
        if (!Configure::read('Yak.Session')) {
            Configure::write('Yak.Session', Configure::read('Session'));
        }
        if ($this->emoji->getCarrier() === 'docomo') {
            Configure::write('Yak.Session.ini',
                             Set::merge(Configure::read('Yak,Session.ini'),
                                        array('session.use_cookies' => 0,
                                              'session.use_only_cookies' => 0,
                                              'session.name' => Configure::read('Session.cookie'),
                                              'url_rewriter.tags' => 'a=href,area=href,frame=src,input=src,form=fakeentry,fieldset=',
                                              'session.use_trans_sid' => 1,
                                              )));
            Configure::write('Security.level', 'medium');
            Configure::write('Session', Configure::read('Yak.Session'));
            // auto start
            CakeSession::start();
        }
    }

    /**
     * startup
     *
     * @param &$controller
     * @return
     */
    public function startup($controller) {
        $controller->helpers[] = 'Yak.Yak';

        if (!empty($controller->request->data)) {
            $controller->request->data = $this->recursiveFilter($controller->request->data, 'input');
        }
    }

    /**
     * recursiveFilter
     *
     * @param $data
     * @param $filter
     * @return
     */
    public function recursiveFilter($data, $filters = 'input'){
        if(is_array($data)){
            foreach($data as $key => $value){
                if (!empty($value)) {
                    $data[$key]= $this->recursiveFilter($value, $filters);
                }
            }
        }else{
            $data = $this->filter($data, $filters);
        }
        return $data;
    }

    /**
     * filter
     *
     * @param
     * @return
     */
    public function filter($text, $filters = 'input') {
        if ($filters === 'input') {
            if ($this->emoji->isSjisCarrier()) {
                if (mb_detect_encoding($text) === 'UTF-8' || mb_detect_encoding($text) === 'ASCII') {
                    return $this->emoji->filter($text, array('HexToUtf8', 'DecToUtf8'));
                }
                return $this->emoji->filter($text, 'input');
            } else {
                // UTF-8
                return $this->emoji->filter($text, 'input');
            }
        }
        return $this->emoji->filter($text, $filters);
    }

    /**
     * generateRedirectUrl
     *
     * @param $url
     * @return $url
     */
    public function generateRedirectUrl($url){
        if ($this->emoji->getCarrier() == 'docomo') {
            if(is_array($url)) {
                if(!isset($url['?'])) {
                    $url['?'] = array();
                }
                $url['?'][session_name()] = session_id();
            }else {
                if(strpos($url, '?') === false) {
                    $url .= '?';
                }else {
                    $url .= '&';
                }
                $url .= sprintf("%s=%s", session_name(), urlencode(session_id()));
            }
            return $url;
        }
        return $url;
    }
}