<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request){

        $request->validate([ 'email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
        ? response()->json(['message' => 'Lien de reinitialisation envoye'])
        : response()->json(['error' => 'Eurreur lors de l\'envoi']);
    }

    public function resetPassword(Request $request){
        $request->validate([
            'email' => 'request|email',
            'password' => 'required|confirmed:min:6',
            'token' => 'required'
        ]);

        $status =  Password::reset($request->only('email','password','password_confirmation','token'),
        function($user, $password){
            $user->forceFill(['password' => bcrypt($password)])->save();

        });

        return $status === password::PASSWORD_RESET
            ? response()->json(['message' => 'Mot de passe reinitialise avec succes'])
            : response()->json(['error' => 'Jetons de reinitialisation invalide ou expire'], 422);
    }
}
