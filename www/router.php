<?php
/**
 * @author   Demin Yin <deminy@deminy.net>
 * @license  MIT license
 */

/**
 * This script simulates 4 types REST services (GET, POST, PUT and DELETE), manipulating employee data which are stored
 * in file "employees.json" in JSON format:
 *     {
 *         "7" : {
 *             "name" : "James Bond",
 *             "age"  : 27
 *         }
 *     }
 */

/**
 * Handle bad HTTP request.
 *
 * @param   string  $message  Message to be returned for a bad HTTP request.
 *
 * @return  void
 */
function badRequest($message)
{
    header('HTTP/1.1 400 Bad Request');
    exit($message);
}

$file = __DIR__ . '/employees.json';

// Get all employees information.
$data = is_readable($file) ? file_get_contents($file) : null;
$employees = !empty($data) ? json_decode($data, true) : array();

// Validate request URL.
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (in_array($_SERVER['REQUEST_URI'], array('', '/'))) {
            exit('OK');
        }
        // NOTE: no break statement here.
    case 'PUT':
        // For PUT requests, variable $_REQUEST might always be empty when using PHP 5.4+ built-in web server.
        $requestData = json_decode(file_get_contents('php://input'), true);
        // NOTE: No break statement here.
    case 'DELETE':
        if (!preg_match('#^/employee/(\d+)$#', $_SERVER['REQUEST_URI'], $matches)) {
            badRequest('Bad REST request.');
        } else {
            $employeeId = (int) $matches[1];
        }
        break;
    case 'POST':
        if ('/employee' != $_SERVER['REQUEST_URI']) {
            badRequest('Bad REST request.');
        } else {
            $requestData = json_decode(file_get_contents('php://input'), true);

            if (is_array($requestData) && array_key_exists('employeeId', $requestData)) {
                $employeeId = (int) $requestData['employeeId'];
            }

            if (empty($employeeId)) {
                badRequest('Unsupported REST request.');
            }
        }
        break;
    default:
        badRequest('Unsupported REST request.');
        break;
}

// Process request.
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        exit(array_key_exists($employeeId, $employees) ? json_encode($employees[$employeeId]) : json_encode(false));
        break;
    case 'POST':
        if (!array_key_exists($employeeId, $employees)) {
            $employees[$employeeId] = array(
                'name' => array_key_exists('name', $requestData) ? $requestData['name'] : null,
                'age'  => array_key_exists('age', $requestData) ? (int) $requestData['age'] : null,
            );
            file_put_contents($file, json_encode($employees));
        } else {
            badRequest('Unable to insert because the employee already exists.');
        }
        break;
    case 'PUT':
        if (array_key_exists($employeeId, $employees)) {
            if (array_key_exists('name', $requestData)) {
                $name = $requestData['name'];
            } else {
                $name = array_key_exists('name', $employees[$employeeId]) ? $employees[$employeeId]['name'] : null;
            }

            if (array_key_exists('age', $requestData)) {
                $age = (int) $requestData['age'];
            } else {
                $age = array_key_exists('age', $employees[$employeeId]) ? $employees[$employeeId]['age'] : null;
            }

            $employees[$employeeId] = array(
                'name' => $name,
                'age'  => $age,
            );
            file_put_contents($file, json_encode($employees));
        } else {
            badRequest('Unable to update because the employee does not exist.');
        }
        break;
    case 'DELETE':
        if (array_key_exists($employeeId, $employees)) {
            unset($employees[$employeeId]);
            file_put_contents($file, json_encode($employees));
        } else {
            badRequest('Unable to delete because the employee does not exist.');
        }
        break;
    default:
        badRequest('Unsupported REST request.');
        break;
}

exit(json_encode(true));
