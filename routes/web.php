<?php

/** @var \Laravel\Lumen\Routing\Router $router */
use Illuminate\Http\Response;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// PHPのセッションを直に起動
session_start();

$router->get('/', function () use ($router) {
    if (isset($_SESSION['access_token'])) {
        $http = new GuzzleHttp\Client;
        $response = $http->request('GET', 'http://localhost:8000/api/user', [
            'headers' => [
                'Accept'     => 'application/json',
                'Authorization' => 'Bearer '.$_SESSION['access_token'],
            ]
        ]);
        $user = json_decode((string)$response->getBody(), true);
        return view('home', ['name' => $user['name']]);
    } else {
        return redirect('login');
    }
});

$redirect_url = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].'/callback';

$router->get('login', function () use ($router, $redirect_url) {
    $query = http_build_query([
        'client_id' => '3', // 作成したクライアントの「Cliend ID」を指定
        'redirect_uri' => $redirect_url,
        'response_type' => 'code',
        'scope' => '',
    ]);

    // Passportサーバーの認可エンドポイントにリダイレクト
    return redirect('http://localhost:8000/oauth/authorize?' . $query);
});

$router->get('callback', function () use ($router, $redirect_url) {
    $http = new GuzzleHttp\Client;
    $response = $http->post('http://localhost:8000/oauth/token', [
        'form_params' => [
          'grant_type' => 'authorization_code',
          'client_id' => '3', // 作成したクライアントの「Cliend ID」を指定
          'client_secret' => 'wiNmbsqBoxvnsPvcf4RgeNx5gTtdS9A1mUiA1Ogz', // 作成したクライアントの「Client secret」を指定
          'redirect_uri' => $redirect_url,
          'code' => $_GET['code'],
        ],
    ]);

    $token = json_decode((string)$response->getBody(), true);
    $_SESSION['access_token'] =  $token['access_token'];
    return redirect('/');
});
