<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\UserService;

class ProfileController
{
    public function show(): void
    {
        Response::view('profile.index', [
            'title'    => 'Your profile',
            'user'     => Auth::user(),
            'success'  => Session::pull('profile_success'),
            'errors'   => Session::pull('profile_errors', []),
            'old'      => Session::pull('profile_old', []),
            'section'  => Session::pull('profile_section', ''),
        ]);
    }

    public function update(): void
    {
        $request = new Request();
        $result  = (new UserService())->updateProfile(
            Auth::user(),
            (string) $request->input('name', ''),
            (string) $request->input('email', ''),
            (string) $request->input('current_password', ''),
            Request::ip(),
        );

        if (!$result['ok']) {
            Session::flash('profile_errors', $result['errors']);
            Session::flash('profile_old', [
                'name'  => (string) $request->input('name', ''),
                'email' => (string) $request->input('email', ''),
            ]);
            Session::flash('profile_section', 'profile');
        } else {
            Session::flash('profile_success', 'Your profile has been updated.');
        }
        Response::redirect('/profile');
    }

    public function password(): void
    {
        $request = new Request();
        $result  = (new UserService())->changePassword(
            Auth::user(),
            (string) $request->input('current_password', ''),
            (string) $request->input('new_password', ''),
            (string) $request->input('confirm_password', ''),
            Request::ip(),
        );

        if (!$result['ok']) {
            Session::flash('profile_errors', $result['errors']);
            Session::flash('profile_section', 'password');
        } else {
            // Other sessions are now invalid (auth_version bumped); keep this one alive.
            Auth::refresh($result['user']);
            Session::flash('profile_success', 'Your password has been changed. Any other signed-in devices have been logged out.');
        }
        Response::redirect('/profile');
    }
}
