<?php

return [
    'password_confirm' => [
        'heading' => 'Confirmation de mot de passe',
        'description' => 'Veuillez confirmer votre mot de passe pour procéder à cette action.',
        'current_password' => 'Mot de passe actuel',
    ],
    'two_factor' => [
        'heading' => 'Authentification à deux facteurs',
        'description' => "Veuillez confirmer l'accès à votre compte en saisissant le code d'authentification fourni par votre application d'authentification.",
        'code_placeholder' => 'XXX-XXX',
        'recovery' => [
            'heading' => 'Authentification à deux facteurs',
            'description' => "Veuillez confirmer l'accès à votre compte en entrant l'un de vos codes de récupération d'urgence.",
        ],
        'recovery_code_placeholder' => 'abcdef-98765',
        'recovery_code_text' => 'Appareil perdu ?',
        'recovery_code_link' => 'Utiliser un code de récupération',
        'back_to_login_link' => 'Retour à la page de connexion',
    ],
    'profile' => [
        'account' => 'Compte',
        'profile' => 'Profil',
        'my_profile' => 'Mon Profil',
        'subheading' => 'Gérer votre compte et vos informations personnelles.',
        'personal_info' => [
            'heading' => 'Informations personnelles',
            'subheading' => 'Gérer vos informations personnelles.',
            'submit' => [
                'label' => 'Mettre à jour',
            ],
            'notify' => 'Mise à jour du profil réussie !',
        ],
        'password' => [
            'heading' => 'Mot de passe',
            'subheading' => 'Doit être de 8 caractères.',
            'submit' => [
                'label' => 'Mettre à jour',
            ],
            'notify' => 'Mot de passe mis à jour avec succès !',
        ],
        '2fa' => [
            'title' => 'Authentification à deux facteurs',
            'description' => "Gérez l'authentification à deux facteurs pour votre compte (recommandé).",
            'actions' => [
                'enable' => 'Activer',
                'regenerate_codes' => 'Régénérer les codes de récupération',
                'disable' => 'Désactiver',
                'confirm_finish' => 'Confirmer et terminer',
                'cancel_setup' => 'Annuler la configuration',
            ],
            'setup_key' => 'Clé de configuration',
            'must_enable' => "Vous devez activer l'authentification à deux facteurs pour utiliser cette application.",
            'not_enabled' => [
                'title' => "Vous n'avez pas activé l'authentification à deux facteurs.",
                'description' => "Lorsque l'authentification à deux facteurs est activée, un jeton sécurisé et aléatoire vous sera demandé lors de l'authentification. Vous pouvez récupérer ce jeton à partir de l'application Google Authenticator de votre téléphone.",
            ],
            'finish_enabling' => [
                'title' => "Terminez l'activation de l'authentification à deux facteurs.",
                'description' => "Pour terminer l'activation de l'authentification à deux facteurs, scannez le code QR suivant à l'aide de l'application d'authentification de votre téléphone ou entrez la clé de configuration et fournissez le code OTP généré.",
            ],
            'enabled' => [
                'notify' => 'Authentification à deux facteurs activée.',
                'title' => "Vous avez activé l'authentification à deux facteurs !",
                'description' => "L'authentification à deux facteurs est maintenant activée. Scannez le code QR suivant en utilisant l'application d'authentification de votre téléphone ou entrez la clé de configuration.",
                'store_codes' => "Conservez ces codes de récupération dans un gestionnaire de mots de passe sécurisé. Ils peuvent être utilisés pour récupérer l'accès à votre compte si votre dispositif d'authentification à deux facteurs est perdu.",
            ],
            'disabling' => [
                'notify' => "L'authentification à deux facteurs a été désactivée.",
            ],
            'regenerate_codes' => [
                'notify' => 'De nouveaux codes de récupération ont été générés.',
            ],
            'confirmation' => [
                'success_notification' => 'Code vérifié. Authentification à deux facteurs activée.',
                'invalid_code' => "Le code que vous avez saisi n'est pas valide.",
            ],
        ],
        'sanctum' => [
            'title' => "Jetons d'API",
            'description' => "Gérez les jetons d'API qui permettent aux services tiers d'accéder à cette application en votre nom. REMARQUE : votre jeton est affiché une fois lors de sa création. Si vous perdez votre jeton, vous devrez le supprimer et en créer un nouveau.",
            'create' => [
                'notify' => 'Jeton créé avec succès !',
                'message' => "Votre jeton ne sera visile qu'une seule fois après sa création. Si vous perdez votre rejetez, vous devrez le supprimer puis en créer un nouveau.",
                'submit' => [
                    'label' => 'Créer',
                ],
            ],
            'update' => [
                'notify' => 'Jeton mis à jour avec succès !',
            ],
            'copied' => [
                'label' => "J'ai copié mon jeton",
            ],
        ],
    ],
    'clipboard' => [
        'link' => 'Copier dans le presse-papiers',
        'tooltip' => 'Copié !',
    ],
    'fields' => [
        'avatar' => 'Avatar',
        'email' => 'E-mail',
        'login' => 'Identifiant',
        'name' => 'Nom',
        'password' => 'Mot de passe',
        'password_confirm' => 'Confirmer le mot de passe',
        'new_password' => 'Nouveau mot de passe',
        'new_password_confirmation' => 'Confirmez le mot de passe',
        'token_name' => 'Nom du jeton',
        'token_expiry' => 'Expiration du jeton',
        'abilities' => 'Aptitudes',
        '2fa_code' => 'Code',
        '2fa_recovery_code' => 'Code de récupération',
        'created' => 'Créé',
        'expires' => 'Expire',
    ],
    'or' => 'Ou',
    'cancel' => 'Annuler',
    // //
    'login' => [
        'username_or_email' => 'Identifiant ou E-mail',
        'forgot_password_link' => 'Mot de passe oublié ?',
        'create_an_account' => 'Créer un compte',
    ],
    'registration' => [
        'title' => "S'inscrire",
        'heading' => 'Créer un nouveau compte',
        'submit' => [
            'label' => "S'inscrire",
        ],
        'notification_unique' => 'Un compte avec cet email existe déjà. Veuillez vous connecter.',
    ],
    'reset_password' => [
        'title' => 'Mot de passe oublié',
        'heading' => 'Réinitialisez votre mot de passe',
        'submit' => [
            'label' => 'Valider',
        ],
        'notification_error' => 'Erreur : veuillez réessayer plus tard.',
        'notification_error_link_text' => 'Try Again',
        'notification_success' => 'Vérifiez votre boîte de réception pour les instructions !',
    ],
    'verification' => [
        'title' => 'Vérifier les courriels',
        'heading' => "Vérification de l'e-mail requise",
        'submit' => [
            'label' => 'Déconnexion',
        ],
        'notification_success' => 'Vérifiez votre boîte de réception pour les instructions !',
        'notification_resend' => "L'e-mail de vérification a été renvoyé.",
        'before_proceeding' => 'Avant de continuer, veuillez vérifier votre e-mail pour un lien de vérification.',
        'not_receive' => "Si vous n'avez pas reçu l'e-mail,",
        'request_another' => 'Cliquez ici pour en demander un autre.',
    ],
];
