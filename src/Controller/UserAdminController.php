<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserAdminType;
use App\Form\SearchUserType;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/user/admin')]
#[IsGranted("ROLE_ADMIN")]
class UserAdminController extends AbstractController
{
    #[Route('/', name: 'app_user_admin_index', methods: ['GET', 'POST'])]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        //on définit le nombre d'éléments par page
        $limit = 25;
        //on récupère le numero de page ou 1
        $page = (int)$request->query->get('page', 1);
        $users = $userRepository->getPaginatedUsers($page, $limit);
        //on récupère le nombre total d'articles
        $total = $userRepository->getTotalUsers();

        $form = $this->createForm(SearchUserType::class);

        $search = $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $users = $userRepository->search($search->get('searchContent')->getData());
        }

        return $this->render('user_admin/index.html.twig', [
            'users' => $users,
            'form' => $form->createView(),
            'page' => $page,
            'total' => $total,
            'limit' => $limit
        ]);
    }

    #[Route('/{id}', name: 'app_user_admin_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user_admin/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserRepository $userRepository): Response
    {
        $form = $this->createForm(UserAdminType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->add($user, true);
            
            $this->addFlash('success', 'Les informations du compte ont bien été modifiée');
            return $this->redirectToRoute('app_user_admin_index', ['_fragment' => 'user-index'], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user_admin/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_admin_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user, true);
            $this->addFlash('success', "L'utilisateur a bien été supprimé");
        }

        return $this->redirectToRoute('app_user_admin_index', ['_fragment' => 'user-index'], Response::HTTP_SEE_OTHER);
    }
}
