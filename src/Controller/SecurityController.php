<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends Controller
{
    /**
     * @Route("/prihlaseni", name="login", methods="GET")
     */
    public function login()
    {
        return $this->render('security/index.html.twig', [
            'controller_name' => 'SecurityController',
        ]);
    }

    /**
     * @Route("/prihlaseni", name="login_check", methods="POST")
     */
    public function loginCheck(Request $request) {
        $url = $request->get('url') . '?' . http_build_query(['gethx' => $request->get('username')]);
        $login_page = @file_get_contents($url);
        
        if ($login_page === false) {
            return new Response("Přihlašovací stránka není dostupná, zkontrolujte URL", Response::HTTP_UNAUTHORIZED);
        }

        $xml = new \DOMDocument();
        @$xml->loadXML($login_page);

        if (!@$xml->schemaValidate(SCHEMAS . 'token.xsd')) {
            return new Response("Špatné přihlašovací jméno", Response::HTTP_UNAUTHORIZED);
        }

        $result =  $xml->getElementsByTagName('results')->item(0);

        $plain = $result->getElementsByTagName('salt')->item(0)->nodeValue .
                $result->getElementsByTagName('ikod')->item(0)->nodeValue . 
                $result->getElementsByTagName('typ')->item(0)->nodeValue . 
                $request->get('password');
        $hash = base64_encode(hash("sha512", $plain, true)); 
        $plain_token = '*login*' . $request->get('username') . '*pwd*' . $hash . '*sgn*ANDR' . date('Ymd');
        $api_token = base64_encode(hash("sha512", $plain_token, true)); 
        $api_token = str_replace('\\', '_', $api_token);
        $api_token = str_replace('/', '_', $api_token);
        $api_token = str_replace('+', '-', $api_token);
        
        $url = $request->get('url') . '?' . http_build_query([
            'hx' => $api_token,
            'pm' => 'login'
        ]);
        
        $data = @file_get_contents($url);
        //TODO: check

        $xml = new \DOMDocument();
        @$xml->loadXML($data);

        if (!$xml->schemaValidate(SCHEMAS . 'login.xsd')) {
            return new Response("Špatné přihlašovací jméno nebo heslo", Response::HTTP_UNAUTHORIZED);
        }

        // Save important stuff to session
        $this->get('session')->set('token', $api_token);
        $this->get('session')->set('url', $request->get('url'));

        // Set role for our fake User
        $token = new UsernamePasswordToken($request->get('username') . $request->get('url'), $request->get('password'), 'secured_area', ['ROLE_USER']);        
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_secured_area', serialize($token));
        return $this->redirectToRoute('main');
    }
}