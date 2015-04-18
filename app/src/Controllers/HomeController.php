<?php

use Philo\Blade\Blade;

class HomeController extends Illuminate\Routing\Controller
{

    private $blade;
    private $viewsDirectory = '../app/Views';
    private $cacheDirectory = '../app/Cache';

    public function __construct()
    {
        // Initiate Blade template system
        $this->blade = new Blade($this->viewsDirectory, $this->cacheDirectory);
    }

    /*
    |--------------------------------------------------------------------------
    | Home Index
    |--------------------------------------------------------------------------
    */
    public function getIndex()
    {
        return $this->blade->view()->make('home');
    }

    /*
    |--------------------------------------------------------------------------
    | Generate URL
    |--------------------------------------------------------------------------
    */
    public function postIndex()
    {
        // Sanitize URL
        $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);
        // Check if the original url is online
        if ($this->checkUrl($url) == false) {
            return json_encode(array('result' => 'error', 'message' => 'Please insert a valid URL'));
        }
        // Check if the original URL already exists in the database
        $exists = Url::whereOriginal(urlencode($url))->first();

        if (!is_null($exists)) {
            $id = $exists['short'];
        } else {

            $save = new Url;
            $save->short = rand();
            $save->original = urlencode($url);
            $save->save();

            $hashids = new Hashids\Hashids('dfztr');
            $save->short = $hashids->encode($save->id);
            $save->save();

            $id = $save->short;

        }
        $result = array('result' => 'success', 'url' => 'http://' . getenv('HTTP_HOST') . '/' . $id);

        return json_encode($result);
    }


    public function getShorten()
    {
        // Sanitize URL
        $url = filter_var($_GET['url'], FILTER_SANITIZE_URL);
        // Check if the original url is online
        if ($this->checkUrl($url) == false) {
            return json_encode(array('result' => 'error', 'message' => 'Please insert a valid URL'));
        }
        // Check if the original URL already exists in the database
        $exists = Url::whereOriginal(urlencode($url))->first();

        if (!is_null($exists)) {
            $id = $exists['short'];
        } else {

            $save = new Url;
            $save->short = rand();
            $save->original = urlencode($url);
            $save->save();

            $hashids = new Hashids\Hashids('dfztr');
            $save->short = $hashids->encode($save->id);

            $save->save();
            $id = $save->short;

        }


        return 'http://' . getenv('HTTP_HOST') . '/' . $id;


    }


    /*
    |--------------------------------------------------------------------------
    | Check if the original URL exists
    |--------------------------------------------------------------------------
    */
    private function checkUrl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $response_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (in_array($response_status, array(404, 0))) {
            return false;
        }
        return true;
    }



    /*
    |--------------------------------------------------------------------------
    | Redirect to the original Url
    |--------------------------------------------------------------------------
    */
    public function goToLink($id)
    {


        if (!is_null($url = Url::whereShort($id)->first())) {
            Url::whereShort($id)->increment('counter');
            return $this->blade->view()->make('redirect')->with('url', urldecode($url['original']['original']));

        } else {
            $this->redirect();
        }


    }


    /*
    |--------------------------------------------------------------------------
    | Helper function to redirect
    |--------------------------------------------------------------------------
    */
    function redirect($url = '/notFound')
    {
        if (!headers_sent()) {
            //If headers not sent yet... then do php redirect
            header('Location: ' . $url);
            exit;
        } else {
            //If headers are sent... do javascript redirect... if javascript disabled, do html redirect.
            echo '<script type="text/javascript">';
            echo 'window.location.href="' . $url . '";';
            echo '</script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
            echo '</noscript>';
            exit;
        }
    }
}
