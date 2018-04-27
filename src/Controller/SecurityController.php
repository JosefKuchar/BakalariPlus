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

        $xml = @simplexml_load_string($login_page);
        

        if ($xml === false) {
            return new Response("Server vrátil nevalidní odpoveď, zkuste to znovu", Response::HTTP_UNAUTHORIZED);
        }

        $children = ['res', 'salt', 'ikod', 'typ'];

        // FIXME
        foreach($children as $child) {
            if (!isset($xml[$child])) {
                // Invalid XML
            }
        }

        if ($xml->res === '02') {
            return new Response("Špatné přihlašovací jméno", Response::HTTP_UNAUTHORIZED);
        }

        $plain = $xml->salt . $xml->ikod . $xml->typ . $request->get('password');
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

        $xml = @simplexml_load_string(@file_get_contents($url));

        if ($xml === false) {
            return new Response("Server vrátil nevalidní odpoveď, zkuste to znovu", Response::HTTP_UNAUTHORIZED);
        }

        if ($xml->results->result == '-1') {
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