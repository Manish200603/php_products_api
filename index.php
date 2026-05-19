<?php
header("Content-Type: application/json");

$dataFile = "products.json";

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

$path = parse_url($requestUri, PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

function loadProducts($file) {
    return json_decode(file_get_contents($file), true);
}

function saveProducts($file, $products) {
    file_put_contents($file, json_encode($products, JSON_PRETTY_PRINT));
}

function sendResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateProduct($data, $partial = false) {
    $errors = [];

    if (!$partial || isset($data['name'])) {
        if (empty($data['name'])) {
            $errors[] = "Name is required.";
        } elseif (strlen($data['name']) > 255) {
            $errors[] = "Name must not exceed 255 characters.";
        }
    }

    if (!$partial || isset($data['price'])) {
        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
            $errors[] = "Price must be a positive number.";
        }
    }

    if (!$partial || isset($data['quantity'])) {
        if (!isset($data['quantity']) || !is_numeric($data['quantity']) || $data['quantity'] < 0) {
            $errors[] = "Quantity must be a non-negative integer.";
        }
    }

    return $errors;
}

$products = loadProducts($dataFile);

if ($segments[0] !== "products") {
    sendResponse(404, ["message" => "Endpoint not found"]);
}

$id = $segments[1] ?? null;

switch ($method) {

    // CREATE PRODUCT
    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input) {
            sendResponse(400, ["message" => "Invalid JSON input"]);
        }

        $errors = validateProduct($input);

        if (!empty($errors)) {
            sendResponse(422, ["errors" => $errors]);
        }

        $newId = count($products) + 1;

        $product = [
            "id" => $newId,
            "name" => $input['name'],
            "description" => $input['description'] ?? "",
            "price" => (float)$input['price'],
            "quantity" => (int)$input['quantity']
        ];

        $products[] = $product;
        saveProducts($dataFile, $products);

        sendResponse(201, [
            "message" => "Product created successfully",
            "data" => $product
        ]);
        break;

    // GET PRODUCT
    case 'GET':
        if (!$id) {
            sendResponse(400, ["message" => "Product ID is required"]);
        }

        foreach ($products as $product) {
            if ($product['id'] == $id) {
                sendResponse(200, $product);
            }
        }

        sendResponse(404, ["message" => "Product not found"]);
        break;

    // UPDATE PRODUCT
    case 'PUT':
        if (!$id) {
            sendResponse(400, ["message" => "Product ID is required"]);
        }

        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input) {
            sendResponse(400, ["message" => "Invalid JSON input"]);
        }

        $errors = validateProduct($input, true);

        if (!empty($errors)) {
            sendResponse(422, ["errors" => $errors]);
        }

        foreach ($products as &$product) {
            if ($product['id'] == $id) {

                if (isset($input['name'])) {
                    $product['name'] = $input['name'];
                }

                if (isset($input['description'])) {
                    $product['description'] = $input['description'];
                }

                if (isset($input['price'])) {
                    $product['price'] = (float)$input['price'];
                }

                if (isset($input['quantity'])) {
                    $product['quantity'] = (int)$input['quantity'];
                }

                saveProducts($dataFile, $products);

                sendResponse(200, [
                    "message" => "Product updated successfully",
                    "data" => $product
                ]);
            }
        }

        sendResponse(404, ["message" => "Product not found"]);
        break;

    default:
        sendResponse(405, ["message" => "Method not allowed"]);
}
?>