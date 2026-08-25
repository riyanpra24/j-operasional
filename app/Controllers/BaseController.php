<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    protected $helpers = ['form', 'url'];

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Remember the last full internal page so refreshing the masked domain
        // can restore the user's current page. AJAX and mutation endpoints are
        // deliberately excluded.
        if (
            session()->get('auth_user_id') !== null
            && $request->getMethod() === 'GET'
            && ! $request->isAJAX()
        ) {
            $segments = service('uri')->getSegments();
            $allowedRoots = [
                'dashboard',
                'dokumen-masuk',
                'dokumen-keluar',
                'distribusi-dokumen',
                'agendaris',
                'kelola-akun',
            ];

            if ($segments !== [] && in_array($segments[0], $allowedRoots, true)) {
                session()->set('auth_last_route', implode('/', $segments));
            }
        }
    }
}
