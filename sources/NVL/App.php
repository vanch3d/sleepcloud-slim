<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 07/03/2018
 * Time: 22:52
 */

namespace NVL;

use Swagger\Annotations as OAS;

/**
 *
 * @OAS\OpenApi(
 *     openapi= SWAGGER_VERSION,
 *     @OAS\Info(
 *          version= API_VERSION,
 *          description="Description of the sleepcloud-slim APIs",
 *          title=  API_NAME,
 *          @OAS\License(
 *              name="MIT",
 *              url="https://github.com/vanch3d/sleepcloud-slim/blob/master/LICENSE"
 *          ),
 *          @OAS\Contact(
 *              name="vanch3d",
 *              url="https://github.com/vanch3d",
 *              email="nicolas.github@calques3d.org"
 *          ),
 *     )
 * )
 *
 * @OAS\Server(
 *     description="sleepcloud-slim DEV server",
 *     url="http://local.sleepcloud.org/api"
 * )
 *
 */

class App extends \Slim\App
{

}