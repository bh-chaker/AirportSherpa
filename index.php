<?php

require __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';

// Init Parse Client
\Parse\ParseClient::initialize( $config['PARSE_APP_ID'], $config['PARSE_REST_KEY'], $config['PARSE_MASTER_KEY'] );
\Parse\ParseClient::setServerURL( $config['PARSE_SERVER_URL'], $config['PARSE_MOUNT_POINT'] );


/*** Helper functions ***/
// Output JSON response
function writeJson($status, $response) {
    $app = \Slim\Slim::getInstance();
    $app->response->setStatus($status);
    $app->response->headers->set('Content-Type', 'application/json');
    $app->response->write(json_encode($response));
}

// Convert ParseObject of class 'Buyer' to Array
function getUserArray($object) {
    return array(
        'userId' => '' . $object->getObjectId(),
        'firstname' => '' . $object->get('firstname'),
        'lastname' => '' . $object->get('lastname'),
        'email' => '' . $object->get('email'),
        'phone' => '' . $object->get('phone')
        );
}

// Convert ParseObject of class 'Order' to Array
function getOrderArray($object) {
    return array(
        'orderId' => '' . $object->getObjectId(),
        'total' => intval($object->get('total')),
        'date' => $object->get('date')->format(DateTime::ISO8601),
        'status' => intval($object->get('status'))
        );
}
/*** END: Helper functions ***/

/*** App routes ***/
$app = new \Slim\Slim();

// Get user from class 'Buyer'
$app->get('/getuser/userId/:userId', function ($userId) {
    $query = new \Parse\ParseQuery("Buyer");

    try {
        $object = $query->get($userId);
        $result = getUserArray($object);
        writeJson(200, $result);
    }
    catch(\Parse\ParseException $e) {
        writeJson(404, array("error" => "User not found."));
    }
});

// Get order from class 'Order'
$app->get('/getorder/orderId/:orderId', function ($orderId) {
    $app = \Slim\Slim::getInstance();

    $query = new \Parse\ParseQuery("Order");
    $query->includeKey("buyer");

    try {
        $object = $query->get($orderId);
        $result = array_merge(getUserArray($object->get('buyer')), getOrderArray($object));
        writeJson(200, $result);
    }
    catch(\Parse\ParseException $e) {
        writeJson(404, array("error" => "Order not found."));
    }
});

// Cancel an order
$app->get('/cancelorder/orderId/:orderId', function ($orderId) {
    $app = \Slim\Slim::getInstance();

    $query = new \Parse\ParseQuery("Order");

    try {
        $object = $query->get($orderId);

        if ($object->get('status') === 2) {
            $result = array('success' => false, 'message' => 'Order already canceled.');
        }
        else {
            $object->set('status', 2);
            $object->save();
            $result = array('success' => true, 'message' => 'Order has been canceled.');
        }

        writeJson(200, $result);
    }
    catch(\Parse\ParseException $e) {
        writeJson(404, array("error" => "Order not found."));
    }
});
/*** END: App routes ***/

$app->run();
