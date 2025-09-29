<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Vérifie si la route books_index existe
        // Si oui, redirige ; sinon, affiche une page d'accueil simple
        $router = $this->container->get('router');

        if ($router->getRouteCollection()->get('books_index')) {
            return $this->redirectToRoute('books_index');
        }

        // Solution de repli si la route books_index n'est pas trouvée
        return $this->render('home/index.html.twig', [
            'message' => 'Bienvenue dans le Gestionnaire de livres !',
        ]);
    }
}
