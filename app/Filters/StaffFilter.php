<?php

declare(strict_types=1);

namespace App\Filters;

use App\Services\AuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Gate for the staff area: anonymous requests redirect to /login,
 * authenticated requests with the wrong role get a 403.
 *
 * The role is never read from the session; AuthService::currentUser()
 * re-reads the row on every request.
 */
final class StaffFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');
        $userId  = $session->get(AuthService::SESSION_USER_ID);

        if ($userId === null) {
            if (strtoupper($request->getMethod()) === 'GET' && $request->getUri()->getPath() !== '/login') {
                $intended = $request->getUri()->getPath();
                $query    = $request->getUri()->getQuery();

                $session->set(
                    AuthService::SESSION_INTENDED,
                    $intended . ($query !== '' ? '?' . $query : ''),
                );
            }

            return redirect()->to('/login');
        }

        $user = service('auth')->currentUser();

        if ($user === null || (int) $user->is_active !== 1) {
            $session->remove([AuthService::SESSION_USER_ID, AuthService::SESSION_INTENDED]);
            $session->destroy();

            return redirect()->to('/login')->with('authError', lang('Auth.sessionExpired'));
        }

        $allowed = $arguments ?? [];

        if ($allowed !== [] && ! in_array($user->role, $allowed, true)) {
            return service('response')
                ->setStatusCode(403)
                ->setBody(view('errors/403'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
