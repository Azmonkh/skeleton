<?php
/**
 * Example of REST controller for DELETE method
 *
 * @category Application
 *
 * @author   Anton Shevchuk
 * @created  19.02.15 16:46
 */
namespace Application;

use Application\Test;
use Bluz\Controller;

return
/**
 * @accept HTML
 * @accept JSON
 * @accept XML
 * @method DELETE
 * @return mixed
 */
function () {
    $restController = new Controller\Rest();
    $restController->setCrud(Test\Crud::getInstance());
    return $restController->methodDelete();
};
