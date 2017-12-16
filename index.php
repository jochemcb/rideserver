<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use \Firebase\JWT\JWT;

use \Model\User;
use \Model\Contact;
use \Model\Car;
use \Model\Ride;
use \Model\Report;

require '../../php/vendor/autoload.php';
require 'model.php';
//require 'database.php';

error_reporting(E_ALL); // Error engine
ini_set('display_errors', true); // Error display
ini_set('log_errors', true); // Error logging
ini_set('error_log', '/Users/Jochem/logs/php.log'); // Logging file
//ini_set('log_errors_max_len', 1024); // Logging file size

$log = new Logger('rideapp');
$log->pushHandler(new StreamHandler('/Users/Jochem/logs/ride.log', Logger::INFO)); // <<< uses a stream
//$log->pushHandler(new StreamHandler('php://stdout', Logger::INFO)); // <<< uses a stream
$log->info('My logger is now ready!');
$log->info($_SERVER['REQUEST_URI']);



// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    };
    $log->info('request method options ');
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
};
$config['displayErrorDetails'] = true;
$app = new \Slim\App(["settings" => $config]);

function check_jwt($autheader)
{
    global $log;
    $jwt = '';
    $email = '';
    $id = 0;
    try {
        if ($autheader != '') {
            $headers = preg_split("/,/", $autheader);
//        $myText = print_r($headers, true);
//        $log->info($myText);
            for ($i = 0; $i <= count($headers) -1; $i++) {
                $arr = preg_split("/[\s,]+/", trim($headers[$i]));
//            list($key, $value) = explode(' ', $item);
//            $log->info($headers[$i]);
//            $log->info('-> ' . $myText);
                if ($arr[0] == 'Bearer') {
                    if (count($arr) == 2) {
                        $jwt = $arr[1];
                    };
                } elseif ($arr[0] == 'User') {
                    if (count($arr) == 2) {
                        $email = $arr[1];
                    }
                };
            }
        }
        $user = new User;
        $record = $user->get_token($email);
        if (count($record) == 1) {
            $token = $record[0]['token'];
            $id  = $record[0]['id'];
            $decodedobj =  JWT::decode($jwt, $token, array('HS256'));
            $decoded = (array) $decodedobj;
            $log->info('email: '. $email . ' token: '.  $jwt. ' id: '.  $decoded['user_id']);
            if ($decoded['user_id'] != $id) {
                $id = 0;
            }
        } else {
            $id = 0;
        }
        return $id;
    } catch (Exception $e) {
        $log->info($msg = $e->getMessage());
        return 0;
    }
}

$app->put('/api/v1/contact/{id}', function (Request $request, Response $response, $args) {
    global $log;
    $log->info('contact put api id ');
    $id = (int)$args['id'];
    $parsedBody = $request->getBody();
    $log->info('contact api ' . $parsedBody);
    $arr = json_decode($parsedBody, true);
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');
    try {
        $user_id = check_jwt($headerValueStrings);
        if ($user_id !=0) {
            $contact = new Contact;
            $contact -> update($user_id, $id, $arr['name'], $arr['street'], $arr['place'], $arr['zip'], $arr['country']);
            $data = array('status' => 'OK', 'message' => 'Contact updated', 'data' => '');
            $response = $response->withJson($data);
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $data = array('status' => 'NOK', 'message' => $msg, 'data' => '');
        $response = $response->withJson($data);
        return $response;
    }
});

$app->delete('/api/v1/contact/{id}', function (Request $request, Response $response, $args) {
    global $log;
    $log->info('contact delete api id');
    $id = (int)$args['id'];
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');
    try {
        $user_id = check_jwt($headerValueStrings);
        if ($user_id !=0) {
            $contact = new Contact;
            $contact -> delete($user_id, $id);
            $data = array('status' => 'OK', 'message' => 'Contact deleted', 'data' => '');
            $response = $response->withJson($data);
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $data = array('status' => 'NOK', 'message' => $msg, 'data' => '');
        $response = $response->withJson($data);
        return $response;
    }
});

$app->post('/api/v1/contact', function (Request $request, Response $response) {
    global $log;
    $parsedBody = $request->getBody();
    $log->info('contact api ' . $parsedBody);
    $arr = json_decode($parsedBody, true);
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');
    try {
        $user_id = check_jwt($headerValueStrings);
        if ($user_id !=0) {
            $contact = new Contact;
            $id = $contact -> create($user_id, $arr['name'], $arr['street'], $arr['place'], $arr['zip'], $arr['country']);
            $log->info('contact api ' . $id);
            $data = array('status' => 'OK', 'message' => 'New contact saved', 'data' => $id);
            $response = $response->withJson($data);
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $data = array('status' => 'NOK', 'message' => $msg, 'data' => '');
        $response = $response->withJson($data);
        return $response;
    }
});

$app->post('/api/v1/car', function (Request $request, Response $response) {
    global $log;
    $parsedBody = $request->getBody();
    $log->info('car api ' . $parsedBody);
    $arr = json_decode($parsedBody, true);
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');
    try {
        $user_id = check_jwt($headerValueStrings);
        if ($user_id !=0) {
            $car = new Car;
            $id = $car -> create($user_id, $arr['name'], $arr['license']);
            $log->info('car api ' . $id);
            $data = array('status' => 'OK', 'message' => 'New car saved', 'data' => $id);
            $response = $response->withJson($data);
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $data = array('status' => 'NOK', 'message' => $msg, 'data' => '');
        $response = $response->withJson($data);
        return $response;
    }
});

$app->put('/api/v1/car/{id}', function (Request $request, Response $response, $args) {
    global $log;
    $log->info('car put api id ');
    $id = (int)$args['id'];
    $parsedBody = $request->getBody();
    $log->info('car api ' . $parsedBody);
    $arr = json_decode($parsedBody, true);
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');
    try {
        $user_id = check_jwt($headerValueStrings);
        if ($user_id !=0) {
            $car = new Car;
            $car -> update($user_id, $id, $arr['name'], $arr['license']);
            $data = array('status' => 'OK', 'message' => 'Car updated', 'data' => '');
            $response = $response->withJson($data);
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $data = array('status' => 'NOK', 'message' => $msg, 'data' => '');
        $response = $response->withJson($data);
        return $response;
    }
});


$app->put('/api/v1/car/{car_id}/ride/{ride_id}', function (Request $request, Response $response, $args) {
    global $log;
    $ride_id = (int)$args['ride_id'];
    $car_id = (int)$args['car_id'];
    $parsedBody = $request->getBody();
    $log->info('ride put api ' . $parsedBody);
    $arr = json_decode($parsedBody, true);
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');
    try {
        $user_id = check_jwt($headerValueStrings);
        if ($user_id !=0) {
            $ride = new Ride;
            $date = list($year, $month, $day) = sscanf($arr['ride_date'], "%d-%d-%d");
            $date_string = "$year-$month-$day";   // 17-12-01
            // checkbox is a 1 element array
            if (empty($arr['isbusiness'])) {
                $isb_value = 'false';
            } else {
                $isb_value = 'true';
            };
            $ride -> update($user_id, $car_id, (int)$ride_id, $date_string, $isb_value, $arr['km'], (int)$arr['contact_id'], $arr['comment']);
            $data = array('status' => 'OK', 'message' => 'Ride updated', 'data' => '');
            $response = $response->withJson($data);
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $log->info('error: ' . $msg);
        $data = array('status' => 'NOK', 'message' => $msg);
        $response = $response->withJson($data);
        return $response;
    }
});

$app->post('/api/v1/car/{car_id}/ride', function (Request $request, Response $response, $args) {
    global $log;
    $car_id = (int)$args['car_id'];
    $parsedBody = $request->getBody();
    $log->info('ride post api ' . $parsedBody);
    $arr = json_decode($parsedBody, true);
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');
    try {
        $user_id = check_jwt($headerValueStrings);
        if ($user_id !=0) {
            $ride = new Ride;
            $date = list($year, $month, $day) = sscanf($arr['ride_date'], "%d-%d-%d");
            $date_string = "$year-$month-$day";   // 17-12-01
            // checkbox is a 1 element array
            if (empty($arr['isbusiness'])) {
                $isb_value = 'false';
            } else {
                $isb_value = 'true';
            };
            $id = $ride->create($user_id, $car_id, $date_string, $isb_value, $arr['km'], (int)$arr['contact_id'], $arr['comment']);
            $data = array('status' => 'OK', 'message' => 'New ride saved', 'data' => $id);
            $response = $response->withJson($data);
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $log->info('error: ' . $msg);
        $data = array('status' => 'NOK', 'message' => $msg);
        $response = $response->withJson($data);
        return $response;
    }
});


$app->get('/createDB', function (Request $request, Response $response) {
    global $log;
//      $parsedBody = $request->getBody();
    $log->info('createdb');
    Model\create_database();
    $user = new User;
    $user->create('test@test.com', 'test');
//    $car = new Car;
//    $car->create(1, '05-xf-56');
//    //  $response->getBody()->write('OK');
//      $response = $response->withJson($data);
//      return $response;
    return '';
});

$app->get('/info', function (Request $request, Response $response) {
    global $log;
//      $parsedBody = $request->getBody();
    $log->info('info');
//    $car = new Car;
//    $car->create(1, '05-xf-56');
//    //  $response->getBody()->write('OK');
//      $response = $response->withJson($data);
//      return $response;
    return 'Server OK';
});

$app->post('/api/v1/login', function (Request $request, Response $response) {
    global $log;
    $parsedBody = $request->getBody();
    $log->info('login -api ' . $parsedBody);
    $arr = json_decode($parsedBody, true);
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    try {
        $user = new User;
        $rarr = $user->api_login($arr['email'], $arr['password']);
        $data = array('status' => 'OK', 'message' => '', 'data' => $rarr );
        $response = $response->withJson($data);
        $log->info($response->getBody());
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $log->info('error: ' . $msg);
        $data = array('status' => 'NOK', 'message' => $msg);
        $response = $response->withJson($data);
        return $response;
    }
});

$app->get('/api/v1/car/{id}', function (Request $request, Response $response) {
    global $log;
    $car_id = (int)$args['id'];
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');
    try {
        $user_id = check_jwt($headerValueStrings);
        if ($user_id != 0) {
            $report = new Report;
            $arr = $report-> list_car($user_id, $id);
            $data = array('status' => 'OK', 'message' => '', 'car' => $arr );
            $response = $response->withJson($data);
            $log->info($response->getBody());
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $log->info('error: ' . $msg);
        $data = array('status' => 'NOK', 'message' => $msg);
        $response = $response->withJson($data);
        return $response;
    }
});
$app->get('/api/v1/userinfo', function (Request $request, Response $response) {
    global $log;
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');
    try {
        $user_id = check_jwt($headerValueStrings);
        if ($user_id != 0) {
            $report = new Report;
            $carr = $report-> list_cars($user_id);
            $arr = $report-> list_contacts($user_id);
            $data = array('status' => 'OK', 'message' => '', 'contacts' => $arr, 'cars' => $carr );
            $response = $response->withJson($data);
            $log->info($headerValueStrings . "--" . $response->getBody());
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $log->info('error: ' . $msg);
        $data = array('status' => 'NOK', 'message' => $msg);
        $response = $response->withJson($data);
        return $response;
    }
});

$app->get('/api/v1/contacts', function (Request $request, Response $response) {
    global $log;
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');

    try {
        $user_id = check_jwt($headerValueStrings);
        $log->info("->". $user_id);
        if ($user_id != 0) {
            $report = new Report;
            $arr = $report-> list_contacts($user_id);
            $data = array('status' => 'OK', 'message' => '', 'contacts' => $arr);
            $response = $response->withJson($data);
            $log->info($headerValueStrings . "--" . $response->getBody());
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $log->info('error: ' . $msg);
        $data = array('status' => 'NOK', 'message' => $msg);
        $response = $response->withJson($data);
        return $response;
    }
});

$app->get('/api/v1/cars', function (Request $request, Response $response) {
    global $log;
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');
    try {
        $user_id = check_jwt($headerValueStrings);
        if ($user_id != 0) {
            $report = new Report;
            $arr = $report-> list_cars($user_id);
            $data = array('status' => 'OK', 'message' => '', 'cars' => $arr );
            $response = $response->withJson($data);
            $log->info($headerValueStrings . "--" . $response->getBody());
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $log->info('error: ' . $msg);
        $data = array('status' => 'NOK', 'message' => $msg);
        $response = $response->withJson($data);
        return $response;
    }
});
$app->get('/api/v1/car/{id}/rides/{offset}/{limit}', function (Request $request, Response $response, $args) {
    global $log;
    $car_id = (int)$args['id'];
    $offset = (int)$args['offset'];
    $limit = (int)$args['limit'];
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');
    try {
        $user_id = check_jwt($headerValueStrings);
        if ($user_id != 0) {
            $report = new Report;
            $log->info('car rides, ofsset: ' . $offset . 'limit:'. $limit);
            $arr = $report-> list_rides($user_id, $car_id, $offset, $limit);
            $count = $report-> count_rides($user_id, $car_id);
            $data = array('status' => 'OK', 'message' => '', 'count' => $count, 'rides' => $arr );
            $response = $response->withJson($data);
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $log->info('error: ' . $msg);
        $data = array('status' => 'NOK', 'message' => $msg);
        $response = $response->withJson($data);
        return $response;
    }
});
$app->get('/api/v1/car/{id}/lastride_contacts', function (Request $request, Response $response, $args) {
    global $log;
    $car_id = (int)$args['id'];
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
    $headerValueStrings = $request->getHeaderLine('Authorization');
    try {
        $user_id = check_jwt($headerValueStrings);
        if ($user_id != 0) {
            $log->info('lastride_contacts ');
            $report = new Report;
            $arr = $report-> list_lastride_contacts($user_id, $car_id);
            $log->info('lastride_contacts 2 ');
            $data = array('status' => 'OK', 'message' => '', 'data' => $arr );
            $response = $response->withJson($data);
            $log->info($response->getBody());
        } else {
            $response = $response->withStatus(401);
        }
        return $response;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $log->info('error: ' . $msg);
        $data = array('status' => 'NOK', 'message' => $msg);
        $response = $response->withJson($data);
        return $response;
    }
});
$app->run();
