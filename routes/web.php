<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

$redirect_url = 'http://localhost:8080/callback';

Route::get('/', function () {
    return view('welcome');
});

session_start();

Route::get('/login', function () use ($redirect_url) {
    $query = http_build_query([
        'client_id' => '4', // 作成したクライアントの「Cliend ID」を指定
        'redirect_uri' => $redirect_url,
        'response_type' => 'code',
        'scope' => '',
    ]);
    // Passportサーバーの認可エンドポイントにリダイレクト
    return redirect('http://localhost:8000/oauth/authorize?' . $query);
});

Route::get('/callback', function () use ($redirect_url) {
    $http = new GuzzleHttp\Client;
    $response = $http->post('http://localhost:8000/oauth/token', [
        'form_params' => [
          'grant_type' => 'authorization_code',
          'client_id' => '4', // 作成したクライアントの「Cliend ID」を指定
          'client_secret' => '6SPOi3QNvjxNewtik1kB0pNd6QfF5ETT3Yc700FH', // 作成したクライアントの「Client secret」を指定
          'redirect_uri' => $redirect_url,
          'code' => $_GET['code'],
        ],
    ]);

    $token = json_decode((string)$response->getBody(), true);
    $_SESSION['access_token'] =  $token['access_token'];
    $_SESSION['refresh_token'] =  $token['refresh_token'];
    return redirect('/');
});

$router->get('/user', function () use ($router) {
    var_dump($_SESSION);
    if (isset($_SESSION['access_token'])) {
        $http = new GuzzleHttp\Client;
        $response = $http->request('GET', 'http://localhost:8000/api/user', [
            'headers' => [
                'Accept'     => 'application/json',
                'Authorization' => 'Bearer '.$_SESSION['access_token'],
            ]
        ]);
        $user = json_decode((string)$response->getBody(), true);
        return view('user', ['name' => $user['name']]);
    } else {
        return redirect('login');
    }
});

$router->get('/refresh', function () use ($redirect_url) {
    // https://laravel.com/docs/10.x/passport#refreshing-tokens
    if (isset($_SESSION['refresh_token'])) {
        $http = new GuzzleHttp\Client;
        $response = $http->post('http://localhost:8000/oauth/token', [
            'form_params' => [
              'grant_type' => 'refresh_token',
              'refresh_token' => $_SESSION['refresh_token'],
              'client_id' => '4', // 作成したクライアントの「Cliend ID」を指定
              'client_secret' => '6SPOi3QNvjxNewtik1kB0pNd6QfF5ETT3Yc700FH', // 作成したクライアントの「Client secret」を指定
              'redirect_uri' => $redirect_url
            ],
        ]);
        var_dump($response->getBody());

        $token = json_decode((string)$response->getBody(), true);
        $_SESSION['access_token'] =  $token['access_token'];
        $_SESSION['refresh_token'] =  $token['refresh_token'];
        return redirect('user');
    } else {
        return redirect('login');
    }
});
