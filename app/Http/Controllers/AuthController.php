<?php

namespace App\Http\Controllers;

use App\Mail\SendOtp;
use App\Models\User;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class Authcontroller extends Controller
{
    protected $verifyOtp;
    protected $twilioService;
    public function __construct(verifyOtp $verifyOtp, TwilioService $twilioService)
    {
        $this->verifyOtp = $verifyOtp;
        $this->twilioService = $twilioService;
    }

    public function register(Request $request)
{
    try {
        // Validation des données avec retour JSON automatique
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string',
            'email' => 'required|string|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
        ]);

        // Création de l'utilisateur
        $user = User::create([
            'name' => $validated['name'],
            'role' => $validated['role'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        // Vérification de la création de l'utilisateur
        if (!$user) {
            return response()->json(['message' => 'Erreur lors de l\'enregistrement de l\'utilisateur.'], 500);
        }

        // Envoi du SMS avec Twilio
        $msg = "Bienvenue sur notre plateforme, votre inscription a bien été enregistrée.";
        $this->twilioService->sendSms($user->phone, $msg);

        // Réponse de succès
        return response()->json([
            'message' => 'Utilisateur enregistré avec succès',
            'user' => $user
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Gestion des erreurs de validation
        return response()->json([
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        // Gestion des autres erreurs (par ex. erreur serveur)
        return response()->json([
            'message' => 'Une erreur est survenue lors de l\'inscription.',
            'error' => $e->getMessage()
        ], 500);
    }
}





    // public function register(Request $request)
    // {
       
        

        // $validated = $request->validate([
        //     'name' => 'required|string|max:255',
        //     'role' => 'required|string',
        //     'email' => 'required|string|email|unique:users',
        //     'phone' => 'required|string|unique:users',
        //     'password' => 'required|string|min:6',
        // ]);

        // $user = User::create([
        //     'name' => $validated['name'],
        //     'role' => $validated['role'],
        //     'email' => $validated['email'],
        //     'phone' => $validated['phone'],
        //     'password' => Hash::make($validated['password']),
        //     'email_verified_at' => now(), // Si vous voulez marquer l'email comme vérifié automatiquement
        //     //'remember_token' => Str::random(10), // Génère un token de session initial
  
        // ]);
        // if(!$user){
        //     return response()->json(['message' => 'Erreur lors de l\'enregistrement de l\'utilisateur'], 500);

        // }else{
            
        //     $msg = "Bienvenue sur notre plateforme, votre inscription a bien été enregistrée.";
        //     $phone = $user->phone;
    
        //         $this->twilioService->sendSms($phone, $msg);
        
        //     return response()->json(['message' => 'User registered successfully', 'user'=>$user], 201);
        // }
    

         // $this->verifyOtp->sendOtp($request, new TwilioService());
   // }
  

    public function login(Request $request){
        $credentials = $request->validate([
            'email' => 'required|email|exists:users,email',//verifier si l'email existe dans la base de donnees
            'password' => 'required',
        ]);

        if(!Auth::attempt($credentials)){
            return response()->json(['message' => 'Adresse ou mot de pasee invalide'], 401); 
        }

        $user = Auth::user();
             $remember = $request->input('rememberMe');//verifier si l'utilisateur a coche la case se souvenir de moi
             $tokenExpiration = $remember ? now()->addDays(30) : now()->addHours(2); //definir la duree de validite du token

             // Créer un token
             $token = $request->user()->createToken('authToken', ['*'], $tokenExpiration);
     
             return response()->json([
                'message' => 'Connexion réussie',
                'token' => $token->plainTextToken,
                'user' => $user,
            ], 200);
    }
  


    public function logout(Request $request){
        //Revoquer uniquement le token ec cours
     $request->user()->currentAccessToken()->delete();
        // Pourrevoquer tous les tokens de l'utilisateurs :
        //$request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out succesfully'],200);
    }

        
}



