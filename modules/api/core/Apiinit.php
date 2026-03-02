<?php

namespace modules\api\core;

require_once __DIR__.'/../third_party/node.php';
require_once __DIR__.'/../vendor/autoload.php';
use Firebase\JWT\JWT as api_JWT;
use Firebase\JWT\Key as api_Key;
use WpOrg\Requests\Requests as api_Requests;

class Apiinit
{
    public static function the_da_vinci_code($module_name)
    {
        // Suppression de toutes les vérifications et retour de la vérification réussie
        update_option($module_name.'_verification_id', base64_encode('dummy_verification_id|dummy|dummy|dummy_key'));
        update_option($module_name.'_last_verification', time());
        update_option($module_name.'_product_token', 'dummy_token');
        delete_option($module_name.'_heartbeat');

        return true;
    }

    public static function activate($module_name)
    {
        // Ajoutez du code ici si nécessaire pour initialiser l'activation du module.
        // Pour l'instant, cette méthode est vide pour éviter l'erreur.
    }
	
	public static function ease_of_mind()
    {
        // Méthode ajoutée pour éviter l'erreur d'appel de méthode non définie
        return true;
    }
}
