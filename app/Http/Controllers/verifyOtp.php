<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class verifyOtp extends Controller
{

    function generateOtp($phone)
    {
        $otp = rand(100000, 999999); // Générer un OTP à 6 chiffres
        Cache::put('otp_' . $phone, $otp, now()->addMinutes(15)); // Sauvegarder l'OTP pour 5 minutes
        return $otp;
    }

    public function sendOtp(Request $request, TwilioService $twilioService)
    {
        $request->validate([
            'phone' => 'required|regex:/^(\+221)[0-9]{9}$/', // Vérifier que le numéro de téléphone est au format international     
        ]);
        $phone = $request->phone;
        $user = User::where('phone', $phone)->first(); // Vérifier si le numéro de téléphone est enregistré
        if(!$user){
            return response()->json(['msg' => 'Numero de telephone non enregistre'],422);
        }else{

        $otp = $this->generateOtp($phone);
        $message = "Votre code OTP est : $otp. Il expire dans 5 minutes.";
        
        $twilioService->sendSms($phone, $message);

        $token = $user->createToken('OTP token')->plaintextToken;
        
        return response()->json(['message' => 'OTP envoyé avec succès','token'=>$token], 200);
        }
    }

    

    function verifyOtp(Request $request){
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $otp = $request->otp;
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'Token manquant'], 422);
        }

        try {
            // Décoder le token Sanctum pour récupérer l'utilisateur
            $user = Auth::user(); // L'utilisateur authentifié par Sanctum
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token invalide ou expiré'], 422);
        }

        $phone = $user->phone; // Récupère le numéro de téléphone de l'utilisateur

        // Vérification du OTP
        $otp = $request->otp;
        $cachedOTP = Cache::get('otp_' . $phone);

        if ($cachedOTP && $cachedOTP == $otp) {
            // Effacez le OTP après utilisation
            Cache::forget('otp_' . $phone);

            return response()->json(['message' => 'OTP vérifié avec succès']);
        }

        return response()->json(['error' => 'OTP invalide ou expiré'], 422);
    }

//lllllllllllllllllllllllll

 
   

    // Réinitialiser le mot de passe en utilisant le code OTP
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^(?:\+221|0)[0-9]{9}$/',
            'otp' => 'required|digits:6',
            'password' => 'required|confirmed|min:6'
        ]);

        $phone = $request->phone;
        $otp = $request->otp;
        $password = $request->password;

        // Vérification du code OTP
        $cachedOtp = Cache::get('otp_' . $phone);
        
        if (!$cachedOtp || $cachedOtp !== $otp) {
            return response()->json(['error' => 'Code OTP invalide ou expiré.'], 422);
        }

        // Trouver l'utilisateur
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
        }

        // Mettre à jour le mot de passe de l'utilisateur
        $user->password = Hash::make($password);
        $user->save();

        // Effacer le code OTP après réinitialisation
        Cache::forget('otp_' . $phone);

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}




