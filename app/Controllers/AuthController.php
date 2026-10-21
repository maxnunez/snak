<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    public function showLogin(): ResponseInterface|string
    {
        if (service('auth')->isAuthenticated()) {
            return redirect()->to('/cashier');
        }

        return view('auth/login', [
            'errors' => session('errors') ?? [],
            'alert'  => session('authError'),
        ]);
    }

    public function login(): ResponseInterface
    {
        $rules = [
            'username' => [
                'label'  => lang('Auth.usernameLabel'),
                'rules'  => 'required',
                'errors' => ['required' => lang('Auth.usernameRequired')],
            ],
            'password' => [
                'label'  => lang('Auth.passwordLabel'),
                'rules'  => 'required',
                'errors' => ['required' => lang('Auth.passwordRequired')],
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/login')
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $credentials = $this->request->getPost(['username', 'password']);

        if (! service('auth')->attempt((string) $credentials['username'], (string) $credentials['password'])) {
            return redirect()->to('/login')
                ->withInput()
                ->with('authError', lang('Auth.loginFailed'));
        }

        return redirect()->to($this->pullIntendedUrl());
    }

    public function logout(): ResponseInterface
    {
        service('auth')->logout();

        return redirect()->to('/login');
    }

    private function pullIntendedUrl(): string
    {
        $intended = session()->get(AuthService::SESSION_INTENDED);
        session()->remove(AuthService::SESSION_INTENDED);

        if (! is_string($intended) || ! str_starts_with($intended, '/') || str_starts_with($intended, '//')) {
            return '/cashier';
        }

        return $intended;
    }
}
